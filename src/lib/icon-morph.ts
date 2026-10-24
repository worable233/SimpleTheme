/**
 * icon-morph — 轻量图标形变引擎
 *
 * 只做一件事：把一条 SVG 路径 `d` 形变（morph）成另一条。
 * 之前的 morphicons 依赖（gzip ~40KB）只为了主题/菜单/播放器几个按钮的动画，
 * 这里用「展平成折线 + 等长重采样 + 弹簧插值」实现，代码量几百行、零依赖。
 *
 * 流程：
 *   1. 解析 d（M/m L/l H/h V/v C/c S/s Q/q T/t A/a Z/z），把所有曲线采样成折线；
 *   2. 每条子路径按弧长均匀重采样成固定点数（N），并做起点对齐；
 *   3. 两边的子路径按「质心距离 + 面积差」贪心配对，多出来的用退化点补；
 *   4. 弹簧动画逐帧插值点坐标，重新拼成 `d` 写回 <path>。
 *
 * 局限（可接受）：折线化后曲线会略微「多边」，但 24px 尺寸 + N=48 肉眼无感。
 */

/** 每个子路径重采样后的点数 */
const SAMPLES = 48

/** 弹簧参数（单位：每秒） */
const SPRINGS = {
  smooth: { stiffness: 170, damping: 30 },
  snappy: { stiffness: 260, damping: 26 },
  bouncy: { stiffness: 300, damping: 16 },
} as const

export type MorphSpring = keyof typeof SPRINGS
export type MorphReducedMotion = 'never' | 'user' | 'always'

export interface MorphOptions {
  spring?: MorphSpring
  reducedMotion?: MorphReducedMotion
}

export interface MorphController {
  /** 立即切换（不带动画） */
  set(d: string): void
  /** 形变到目标图标 */
  morphTo(d: string): void
  destroy(): void
}

type Pt = [number, number]

interface SubPath {
  pts: Pt[]
  closed: boolean
}

// ============================================================
// 路径解析 → 折线
// ============================================================

const TOKEN_RE = /[MmLlHhVvCcSsQqTtAaZz]|-?\d*\.?\d+(?:[eE][-+]?\d+)?/g

/** 三次贝塞尔采样：采样数按控制多边形长度自适应 */
function sampleCubic(p0: Pt, p1: Pt, p2: Pt, p3: Pt, out: Pt[]): void {
  const approx =
    Math.hypot(p1[0] - p0[0], p1[1] - p0[1]) +
    Math.hypot(p2[0] - p1[0], p2[1] - p1[1]) +
    Math.hypot(p3[0] - p2[0], p3[1] - p2[1])
  const steps = Math.max(2, Math.ceil(approx / 1.2))
  for (let i = 1; i <= steps; i++) {
    const t = i / steps
    const mt = 1 - t
    const a = mt * mt * mt
    const b = 3 * mt * mt * t
    const c = 3 * mt * t * t
    const e = t * t * t
    out.push([
      a * p0[0] + b * p1[0] + c * p2[0] + e * p3[0],
      a * p0[1] + b * p1[1] + c * p2[1] + e * p3[1],
    ])
  }
}

/** 端点式圆弧 → 折线点（SVG 规范 F.6.5/F.6.6） */
function sampleArc(
  x0: number,
  y0: number,
  rx: number,
  ry: number,
  rotDeg: number,
  largeArc: boolean,
  sweep: boolean,
  x1: number,
  y1: number,
  out: Pt[],
): void {
  if (rx === 0 || ry === 0) {
    out.push([x1, y1])
    return
  }
  rx = Math.abs(rx)
  ry = Math.abs(ry)
  const phi = (rotDeg * Math.PI) / 180
  const cosPhi = Math.cos(phi)
  const sinPhi = Math.sin(phi)
  const dx2 = (x0 - x1) / 2
  const dy2 = (y0 - y1) / 2
  const x1p = cosPhi * dx2 + sinPhi * dy2
  const y1p = -sinPhi * dx2 + cosPhi * dy2

  const lambda = (x1p * x1p) / (rx * rx) + (y1p * y1p) / (ry * ry)
  if (lambda > 1) {
    const s = Math.sqrt(lambda)
    rx *= s
    ry *= s
  }

  const rx2 = rx * rx
  const ry2 = ry * ry
  const denom = rx2 * y1p * y1p + ry2 * x1p * x1p
  let coef = Math.sqrt(Math.max(0, (rx2 * ry2 - denom) / denom))
  if (largeArc === sweep) coef = -coef
  const cxp = (coef * rx * y1p) / ry
  const cyp = (-coef * ry * x1p) / rx

  const cx = cosPhi * cxp - sinPhi * cyp + (x0 + x1) / 2
  const cy = sinPhi * cxp + cosPhi * cyp + (y0 + y1) / 2

  const angle = (ux: number, uy: number, vx: number, vy: number) => {
    const dot = ux * vx + uy * vy
    const len = Math.hypot(ux, uy) * Math.hypot(vx, vy)
    const a = Math.acos(Math.min(1, Math.max(-1, dot / (len || 1))))
    return ux * vy - uy * vx < 0 ? -a : a
  }

  const theta1 = angle(1, 0, (x1p - cxp) / rx, (y1p - cyp) / ry)
  let delta = angle(
    (x1p - cxp) / rx,
    (y1p - cyp) / ry,
    (-x1p - cxp) / rx,
    (-y1p - cyp) / ry,
  )
  if (!sweep && delta > 0) delta -= Math.PI * 2
  else if (sweep && delta < 0) delta += Math.PI * 2

  const steps = Math.max(2, Math.ceil((Math.abs(delta) * Math.max(rx, ry)) / 1.2))
  for (let i = 1; i <= steps; i++) {
    const t = theta1 + (delta * i) / steps
    const px = Math.cos(t) * rx
    const py = Math.sin(t) * ry
    out.push([cosPhi * px - sinPhi * py + cx, sinPhi * px + cosPhi * py + cy])
  }
}

/** 把 d 解析成折线子路径 */
export function flattenPath(d: string): SubPath[] {
  const tokens = d.match(TOKEN_RE)
  if (!tokens) return []

  const subpaths: SubPath[] = []
  let current: SubPath | null = null
  let x = 0
  let y = 0
  let startX = 0
  let startY = 0
  // 上一段曲线的第二控制点（S/T 反射用）
  let lastCubicCtrl: Pt | null = null
  let lastQuadCtrl: Pt | null = null
  let i = 0
  let cmd = ''

  const num = () => Number(tokens[i++])
  const ensure = () => {
    if (!current) {
      current = { pts: [[x, y]], closed: false }
      subpaths.push(current)
    }
  }

  while (i < tokens.length) {
    if (/[A-Za-z]/.test(tokens[i] ?? '')) {
      cmd = tokens[i++] ?? ''
    } else if (!cmd) {
      i++
      continue
    }

    const rel = cmd === cmd.toLowerCase()
    const ox = rel ? x : 0
    const oy = rel ? y : 0

    switch (cmd.toUpperCase()) {
      case 'M': {
        x = ox + num()
        y = oy + num()
        startX = x
        startY = y
        current = { pts: [[x, y]], closed: false }
        subpaths.push(current)
        // 后续隐式坐标按 L 处理
        cmd = rel ? 'l' : 'L'
        lastCubicCtrl = null
        lastQuadCtrl = null
        break
      }
      case 'L': {
        ensure()
        x = ox + num()
        y = oy + num()
        current!.pts.push([x, y])
        lastCubicCtrl = null
        lastQuadCtrl = null
        break
      }
      case 'H': {
        ensure()
        x = (rel ? x : 0) + num()
        current!.pts.push([x, y])
        lastCubicCtrl = null
        lastQuadCtrl = null
        break
      }
      case 'V': {
        ensure()
        y = (rel ? y : 0) + num()
        current!.pts.push([x, y])
        lastCubicCtrl = null
        lastQuadCtrl = null
        break
      }
      case 'C': {
        ensure()
        const c1: Pt = [ox + num(), oy + num()]
        const c2: Pt = [ox + num(), oy + num()]
        const p: Pt = [ox + num(), oy + num()]
        sampleCubic([x, y], c1, c2, p, current!.pts)
        lastCubicCtrl = c2
        lastQuadCtrl = null
        x = p[0]
        y = p[1]
        break
      }
      case 'S': {
        ensure()
        const c1: Pt = lastCubicCtrl
          ? [2 * x - lastCubicCtrl[0], 2 * y - lastCubicCtrl[1]]
          : [x, y]
        const c2: Pt = [ox + num(), oy + num()]
        const p: Pt = [ox + num(), oy + num()]
        sampleCubic([x, y], c1, c2, p, current!.pts)
        lastCubicCtrl = c2
        lastQuadCtrl = null
        x = p[0]
        y = p[1]
        break
      }
      case 'Q': {
        ensure()
        const q: Pt = [ox + num(), oy + num()]
        const p: Pt = [ox + num(), oy + num()]
        const c1: Pt = [x + (2 / 3) * (q[0] - x), y + (2 / 3) * (q[1] - y)]
        const c2: Pt = [p[0] + (2 / 3) * (q[0] - p[0]), p[1] + (2 / 3) * (q[1] - p[1])]
        sampleCubic([x, y], c1, c2, p, current!.pts)
        lastQuadCtrl = q
        lastCubicCtrl = null
        x = p[0]
        y = p[1]
        break
      }
      case 'T': {
        ensure()
        const q: Pt = lastQuadCtrl ? [2 * x - lastQuadCtrl[0], 2 * y - lastQuadCtrl[1]] : [x, y]
        const p: Pt = [ox + num(), oy + num()]
        const c1: Pt = [x + (2 / 3) * (q[0] - x), y + (2 / 3) * (q[1] - y)]
        const c2: Pt = [p[0] + (2 / 3) * (q[0] - p[0]), p[1] + (2 / 3) * (q[1] - p[1])]
        sampleCubic([x, y], c1, c2, p, current!.pts)
        lastQuadCtrl = q
        lastCubicCtrl = null
        x = p[0]
        y = p[1]
        break
      }
      case 'A': {
        ensure()
        const rx = num()
        const ry = num()
        const rot = num()
        const largeArc = num() !== 0
        const sweep = num() !== 0
        const nx = ox + num()
        const ny = oy + num()
        sampleArc(x, y, rx, ry, rot, largeArc, sweep, nx, ny, current!.pts)
        x = nx
        y = ny
        lastCubicCtrl = null
        lastQuadCtrl = null
        break
      }
      case 'Z': {
        if (current) {
          current.closed = true
          current.pts.push([startX, startY])
          x = startX
          y = startY
        }
        lastCubicCtrl = null
        lastQuadCtrl = null
        break
      }
      default:
        i++
    }
  }

  return subpaths.filter((s) => s.pts.length > 1)
}

// ============================================================
// 重采样与配对
// ============================================================

/** 按弧长把折线均匀重采样成 n 个点 */
function resample(pts: Pt[], n: number): Pt[] {
  const lens: number[] = [0]
  let total = 0
  for (let i = 1; i < pts.length; i++) {
    const cur = pts[i]!
    const prev = pts[i - 1]!
    total += Math.hypot(cur[0] - prev[0], cur[1] - prev[1])
    lens.push(total)
  }
  if (total === 0) {
    const p = pts[0] ?? [0, 0]
    return Array.from({ length: n }, () => [p[0], p[1]] as Pt)
  }
  const out: Pt[] = []
  let seg = 1
  for (let k = 0; k < n; k++) {
    const target = (total * k) / (n - 1)
    while (seg < lens.length - 1 && (lens[seg] ?? 0) < target) seg++
    const l0 = lens[seg - 1] ?? 0
    const l1 = lens[seg] ?? l0
    const t = l1 === l0 ? 0 : (target - l0) / (l1 - l0)
    const a = pts[seg - 1] ?? pts[0] ?? [0, 0]
    const b = pts[seg] ?? a
    out.push([a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t])
  }
  return out
}

function centroid(pts: Pt[]): Pt {
  let sx = 0
  let sy = 0
  for (const p of pts) {
    sx += p[0]
    sy += p[1]
  }
  return [sx / pts.length, sy / pts.length]
}

/** 子路径包围盒对角线长度，作为「大小」的粗略度量 */
function extent(pts: Pt[]): number {
  let minX = Infinity
  let minY = Infinity
  let maxX = -Infinity
  let maxY = -Infinity
  for (const p of pts) {
    if (p[0] < minX) minX = p[0]
    if (p[0] > maxX) maxX = p[0]
    if (p[1] < minY) minY = p[1]
    if (p[1] > maxY) maxY = p[1]
  }
  return Math.hypot(maxX - minX, maxY - minY)
}

/** 旋转对齐：让 b 的起点与 a 的起点尽量贴近 */
function alignStart(a: Pt[], b: Pt[]): Pt[] {
  const n = a.length
  let bestShift = 0
  let bestCost = Infinity
  for (let shift = 0; shift < n; shift++) {
    let cost = 0
    for (let i = 0; i < n; i += 4) {
      const p = a[i]
      const q = b[(i + shift) % n]
      if (!p || !q) continue
      const dx = p[0] - q[0]
      const dy = p[1] - q[1]
      cost += dx * dx + dy * dy
      if (cost >= bestCost) break
    }
    if (cost < bestCost) {
      bestCost = cost
      bestShift = shift
    }
  }
  if (bestShift === 0) return b
  const out: Pt[] = []
  for (let i = 0; i < n; i++) out.push(b[(i + bestShift) % n]!)
  return out
}

interface PreparedShape {
  loops: Pt[][]
  closed: boolean[]
}

function prepare(d: string): PreparedShape {
  const subs = flattenPath(d)
  return {
    loops: subs.map((s) => resample(s.pts, SAMPLES)),
    closed: subs.map((s) => s.closed),
  }
}

/** 配对代价超过这个值就认为「不是同一个部位」，让它从质心长出来/缩回去，
 *  比硬拉成一条线干净（例如太阳光芒 vs 显示器支架）。单位是 viewBox 坐标。 */
const COLLAPSE_COST = 5

interface Pair {
  from: Pt[]
  to: Pt[]
  closed: boolean
  /** 目标图上没有对应部位：收缩成点并淡出，动画结束剔除 */
  dead?: boolean
  /** 起点图上没有对应部位（目标图多出来）：从点长出来并淡入 */
  born?: boolean
  /** 起点/终点这一条的大小（viewBox 单位），用于「缩到快没了才淡出」 */
  fromExtent?: number
  toExtent?: number
}

/** 贪心配对两个形状的子路径，多出来的用退化点补。
 *  面积最大的一条（主体）无条件配对，否则「太阳圆圈 → 显示器屏幕」会因为尺寸差
 *  超阈值而被判成「各自消失/出现」，整幅图就退化成淡入淡出了。 */
function pairLoops(a: PreparedShape, b: PreparedShape): Pair[] {
  const usedB = new Set<number>()
  const pairs: Pair[] = []

  let dominant = -1
  let dominantExtent = -1
  for (let i = 0; i < a.loops.length; i++) {
    const e = extent(a.loops[i]!)
    if (e > dominantExtent) {
      dominantExtent = e
      dominant = i
    }
  }

  for (let i = 0; i < a.loops.length; i++) {
    const loopA = a.loops[i]!
    const ca = centroid(loopA)
    const ea = extent(loopA)
    let best = -1
    let bestCost = Infinity
    for (let j = 0; j < b.loops.length; j++) {
      if (usedB.has(j)) continue
      const loopB = b.loops[j]!
      const cb = centroid(loopB)
      const eb = extent(loopB)
      const cost = Math.hypot(ca[0] - cb[0], ca[1] - cb[1]) + Math.abs(ea - eb)
      if (cost < bestCost) {
        bestCost = cost
        best = j
      }
    }
    // 主体无条件配对，否则「太阳圆圈 → 显示器屏幕」这种尺寸差超阈值的
    // 会被判成各自消失/出现，整幅图标就退化成淡入淡出（中间还会有一帧全空）
    const accept = best >= 0 && (bestCost <= COLLAPSE_COST || i === dominant)
    if (accept) {
      usedB.add(best)
      const target = b.loops[best]!
      pairs.push({
        from: loopA,
        to: alignStart(loopA, target),
        closed: a.closed[i] || b.closed[best] || false,
        fromExtent: ea,
        toExtent: extent(target),
      })
    } else {
      pairs.push({
        from: loopA,
        to: collapseTo(loopA),
        closed: a.closed[i] || false,
        dead: true,
        fromExtent: ea,
        toExtent: 0,
      })
    }
  }

  // b 多出来的子路径：从 a 的质心「长出来」
  for (let j = 0; j < b.loops.length; j++) {
    if (usedB.has(j)) continue
    const loopB = b.loops[j]!
    pairs.push({
      from: collapseTo(loopB),
      to: loopB,
      closed: b.closed[j] || false,
      born: true,
      fromExtent: 0,
      toExtent: extent(loopB),
    })
  }
  return pairs
}

/** 生成一组塌缩到质心的点（用于「长出来 / 缩回去」） */
function collapseTo(pts: Pt[]): Pt[] {
  const c = centroid(pts)
  return pts.map(() => [c[0], c[1]] as Pt)
}

/** 单条子路径 → d 字符串（一个小数都没省，形状靠重采样保证） */
function loopToPath(pts: Pt[], closed: boolean): string {
  if (!pts.length) return ''
  const first = pts[0]!
  let d = `M${first[0].toFixed(2)} ${first[1].toFixed(2)}`
  for (let k = 1; k < pts.length; k++) {
    const pt = pts[k]!
    d += `L${pt[0].toFixed(2)} ${pt[1].toFixed(2)}`
  }
  if (closed) d += 'Z'
  return d
}

function pointsToPath(loops: Pt[][], closed: boolean[]): string {
  let d = ''
  for (let i = 0; i < loops.length; i++) d += loopToPath(loops[i]!, closed[i] || false)
  return d
}

function smoothstep(edge0: number, edge1: number, x: number): number {
  const t = Math.min(1, Math.max(0, (x - edge0) / (edge1 - edge0)))
  return t * t * (3 - 2 * t)
}

/** 一组配对在进度 p 时的折线 */
function interpolateLoop(pair: Pair, p: number): Pt[] {
  const pts: Pt[] = []
  for (let i = 0; i < pair.from.length; i++) {
    const a = pair.from[i]
    const b = pair.to[i]
    if (!a || !b) continue
    pts.push([a[0] + (b[0] - a[0]) * p, a[1] + (b[1] - a[1]) * p])
  }
  return pts
}

/** 这一条的不透明度：按「当前大小 / 参考大小」决定，而不是按进度。
 *  这样只有真的缩成一个点（或刚从点长出来）的部位才淡出/淡入，
 *  主体即使在换形也是实心的，不会整幅图标同时变淡。 */
function loopOpacity(pair: Pair, pts: Pt[]): number {
  if (!pair.dead && !pair.born) return 1
  const cur = extent(pts)
  const ref = pair.dead ? pair.fromExtent || 0 : pair.toExtent || 0
  if (ref <= 0.001) return pair.dead ? 0 : 1
  // 缩到参考大小的 18% 以下完全透明，50% 以上全不透明
  return smoothstep(0.18, 0.5, cur / ref)
}

// ============================================================
// 控制器
// ============================================================

function prefersReducedMotion(): boolean {
  return (
    typeof window !== 'undefined' &&
    typeof window.matchMedia === 'function' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches
  )
}

const SVG_NS = 'http://www.w3.org/2000/svg'

/**
 * 绑定一个 <svg>，之后用 set()/morphTo() 切换图标。
 *
 * 为什么是「一个 svg + 多条 <path>」而不是「一条 path 拼所有子路径」：
 * 形变时多出来的部位要收缩成点 / 从点长出来，如果全塞在一条 path 里，
 * 那些点既没法单独淡出，也会在动画落定时突兀消失。分条渲染后每条能各自
 * 控制不透明度，收尾干净。
 *
 * 同一时刻只有一个动画在跑；动画中途切目标会从当前帧继续，不会跳。
 */
export function attachMorph(svgEl: SVGSVGElement, options: MorphOptions = {}): MorphController {
  const spring = SPRINGS[options.spring ?? 'snappy']
  const respectMotion = options.reducedMotion ?? 'user'

  /** 当前显示的形状（动画中途切换时作为新起点） */
  let currentLoops: Pt[][] = []
  let currentClosed: boolean[] = []
  let pairs: Pair[] = []
  const pool: SVGPathElement[] = []
  let progress = 1
  let velocity = 0
  let raf = 0
  let lastTime = 0
  let destroyed = false

  function ensurePool(n: number): void {
    while (pool.length < n) {
      const el = document.createElementNS(SVG_NS, 'path') as SVGPathElement
      svgEl.appendChild(el)
      pool.push(el)
    }
  }

  function trimPool(n: number): void {
    while (pool.length > n) pool.pop()?.remove()
  }

  function render(p: number): void {
    const loops: Pt[][] = []
    const closed: boolean[] = []
    const alphas: number[] = []
    for (const pair of pairs) {
      // 要消失的部位提前缩，配合「缩到很小才淡出」才看不出突兀
      const t = pair.dead ? smoothstep(0, 0.85, p) : p
      const pts = interpolateLoop(pair, t)
      loops.push(pts)
      closed.push(pair.closed)
      alphas.push(loopOpacity(pair, pts))
    }
    ensurePool(loops.length)
    for (let i = 0; i < pool.length; i++) {
      const el = pool[i]!
      const d = loops[i] ? loopToPath(loops[i]!, closed[i] || false) : ''
      if (el.getAttribute('d') !== d) el.setAttribute('d', d)
      const alpha = alphas[i]
      if (alpha === undefined || alpha <= 0.01) {
        el.setAttribute('opacity', '0')
      } else if (alpha < 0.99) {
        el.setAttribute('opacity', alpha.toFixed(3))
      } else {
        el.removeAttribute('opacity')
      }
    }
    currentLoops = loops
    currentClosed = closed
  }

  /** 动画落定：丢掉已缩没/已长成的临时部位，回收多余 path */
  function finish(): void {
    const settled = pairs.filter((pair) => !pair.dead)
    for (const pair of settled) delete pair.born
    pairs = settled
    render(1)
    trimPool(pairs.length)
  }

  function tick(time: number): void {
    if (destroyed) return
    const dt = Math.min(0.032, lastTime ? (time - lastTime) / 1000 : 0.016)
    lastTime = time
    // 简谐弹簧（半隐式欧拉）：目标位移 1
    const accel = spring.stiffness * (1 - progress) - spring.damping * velocity
    velocity += accel * dt
    progress += velocity * dt
    if (Math.abs(1 - progress) < 0.0005 && Math.abs(velocity) < 0.01) {
      progress = 1
      velocity = 0
      raf = 0
      lastTime = 0
      finish()
      return
    }
    render(progress)
    raf = requestAnimationFrame(tick)
  }

  function start(): void {
    if (raf) return
    lastTime = 0
    raf = requestAnimationFrame(tick)
  }

  return {
    set(next: string) {
      const shape = prepare(next)
      pairs = shape.loops.map((pts, i) => ({
        from: pts,
        to: pts,
        closed: shape.closed[i] || false,
      }))
      progress = 1
      velocity = 0
      if (raf) {
        cancelAnimationFrame(raf)
        raf = 0
      }
      render(1)
      trimPool(pairs.length)
    },
    morphTo(next: string) {
      const shape = prepare(next)
      if (!shape.loops.length) return
      if (!currentLoops.length) {
        this.set(next)
        return
      }
      progress = 0
      velocity = 0
      // 起点 = 当前屏幕上正在显示的帧（中途切换不跳变）
      const fromShape: PreparedShape = { loops: currentLoops, closed: currentClosed }
      pairs = pairLoops(fromShape, shape)
      if (respectMotion === 'always' || (respectMotion === 'user' && prefersReducedMotion())) {
        progress = 1
        finish()
        return
      }
      start()
    },
    destroy() {
      destroyed = true
      if (raf) cancelAnimationFrame(raf)
      raf = 0
    },
  }
}

/** 供没有 <svg> 的场景使用：把 d 字符串规范成折线路径（不改形状，只统一点数） */
export function normalizePathForMorph(d: string): string {
  const shape = prepare(d)
  return pointsToPath(shape.loops, shape.closed)
}
