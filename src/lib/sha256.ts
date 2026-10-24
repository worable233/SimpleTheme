/**
 * sha256 — 极简同步实现，只给验证码 PoW 用。
 *
 * 为什么不引第三方 sha256：整个用途就是「对 `salt + number` 做一次摘要」，
 * 几十行足够，也避免为了一个 PoW 再拉一个库（altcha 的 widget 我们本来也不要了）。
 * 消息长度在 50 字节上下，走单 block 快路径；更长输入也能正确处理。
 */

const K = new Uint32Array([
  0x428a2f98, 0x71374491, 0xb5c0fbcf, 0xe9b5dba5, 0x3956c25b, 0x59f111f1, 0x923f82a4, 0xab1c5ed5,
  0xd807aa98, 0x12835b01, 0x243185be, 0x550c7dc3, 0x72be5d74, 0x80deb1fe, 0x9bdc06a7, 0xc19bf174,
  0xe49b69c1, 0xefbe4786, 0x0fc19dc6, 0x240ca1cc, 0x2de92c6f, 0x4a7484aa, 0x5cb0a9dc, 0x76f988da,
  0x983e5152, 0xa831c66d, 0xb00327c8, 0xbf597fc7, 0xc6e00bf3, 0xd5a79147, 0x06ca6351, 0x14292967,
  0x27b70a85, 0x2e1b2138, 0x4d2c6dfc, 0x53380d13, 0x650a7354, 0x766a0abb, 0x81c2c92e, 0x92722c85,
  0xa2bfe8a1, 0xa81a664b, 0xc24b8b70, 0xc76c51a3, 0xd192e819, 0xd6990624, 0xf40e3585, 0x106aa070,
  0x19a4c116, 0x1e376c08, 0x2748774c, 0x34b0bcb5, 0x391c0cb3, 0x4ed8aa4a, 0x5b9cca4f, 0x682e6ff3,
  0x748f82ee, 0x78a5636f, 0x84c87814, 0x8cc70208, 0x90befffa, 0xa4506ceb, 0xbef9a3f7, 0xc67178f2,
])

/** 复用的消息扩展缓冲，避免每次求解都分配 */
const W = new Uint32Array(64)

function utf8Bytes(input: string): Uint8Array {
  if (typeof TextEncoder !== 'undefined') return new TextEncoder().encode(input)
  const out: number[] = []
  for (let i = 0; i < input.length; i++) {
    const c = input.charCodeAt(i)
    if (c < 0x80) out.push(c)
    else if (c < 0x800) out.push(0xc0 | (c >> 6), 0x80 | (c & 63))
    else out.push(0xe0 | (c >> 12), 0x80 | ((c >> 6) & 63), 0x80 | (c & 63))
  }
  return new Uint8Array(out)
}

function rotr(x: number, n: number): number {
  return (x >>> n) | (x << (32 - n))
}

/** 处理一个 64 字节块，更新 h（长度 8 的中间状态） */
function block(bytes: Uint8Array, offset: number, h: Uint32Array): void {
  for (let i = 0; i < 16; i++) {
    const j = offset + i * 4
    W[i] = ((bytes[j]! << 24) | (bytes[j + 1]! << 16) | (bytes[j + 2]! << 8) | bytes[j + 3]!) >>> 0
  }
  for (let i = 16; i < 64; i++) {
    const w15 = W[i - 15]!
    const w2 = W[i - 2]!
    const s0 = (rotr(w15, 7) ^ rotr(w15, 18) ^ (w15 >>> 3)) >>> 0
    const s1 = (rotr(w2, 17) ^ rotr(w2, 19) ^ (w2 >>> 10)) >>> 0
    W[i] = (W[i - 16]! + s0 + W[i - 7]! + s1) >>> 0
  }

  let a = h[0]!
  let b = h[1]!
  let c = h[2]!
  let d = h[3]!
  let e = h[4]!
  let f = h[5]!
  let g = h[6]!
  let k = h[7]!

  for (let i = 0; i < 64; i++) {
    const S1 = (rotr(e, 6) ^ rotr(e, 11) ^ rotr(e, 25)) >>> 0
    const ch = ((e & f) ^ (~e & g)) >>> 0
    const t1 = (k + S1 + ch + K[i]! + W[i]!) >>> 0
    const S0 = (rotr(a, 2) ^ rotr(a, 13) ^ rotr(a, 22)) >>> 0
    const maj = ((a & b) ^ (a & c) ^ (b & c)) >>> 0
    const t2 = (S0 + maj) >>> 0
    k = g
    g = f
    f = e
    e = (d + t1) >>> 0
    d = c
    c = b
    b = a
    a = (t1 + t2) >>> 0
  }

  h[0] = (h[0]! + a) >>> 0
  h[1] = (h[1]! + b) >>> 0
  h[2] = (h[2]! + c) >>> 0
  h[3] = (h[3]! + d) >>> 0
  h[4] = (h[4]! + e) >>> 0
  h[5] = (h[5]! + f) >>> 0
  h[6] = (h[6]! + g) >>> 0
  h[7] = (h[7]! + k) >>> 0
}

const HEX = '0123456789abcdef'

/** 计算字符串的 SHA-256，返回小写十六进制 */
export function sha256Hex(input: string): string {
  const msg = utf8Bytes(input)
  const bitLen = msg.length * 8
  // padding：0x80 + 若干 0 + 64 位长度，保证总长是 64 的倍数
  const padded = new Uint8Array(((msg.length + 9 + 63) >> 6) << 6)
  padded.set(msg)
  padded[msg.length] = 0x80
  const view = new DataView(padded.buffer)
  view.setUint32(padded.length - 8, Math.floor(bitLen / 0x100000000))
  view.setUint32(padded.length - 4, bitLen >>> 0)

  const h = new Uint32Array([
    0x6a09e667, 0xbb67ae85, 0x3c6ef372, 0xa54ff53a, 0x510e527f, 0x9b05688c, 0x1f83d9ab, 0x5be0cd19,
  ])
  for (let off = 0; off < padded.length; off += 64) block(padded, off, h)

  let out = ''
  for (let i = 0; i < 8; i++) {
    const v = h[i]!
    for (let shift = 28; shift >= 0; shift -= 4) out += HEX[(v >>> shift) & 15]
  }
  return out
}
