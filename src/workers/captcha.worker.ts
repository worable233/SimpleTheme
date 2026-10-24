/**
 * 验证码求解 worker：暴力枚举 number，直到 sha256(salt + number) === challenge。
 *
 * 上限由服务端下发（ALTCHA_MAX_NUMBER，当前 50000），最坏情况也就几万次
 * 摘要运算，放 worker 里跑是为了不卡住输入框打字。
 */
import { sha256Hex } from '@/lib/sha256'

export interface SolveRequest {
  salt: string
  challenge: string
  max: number
}

export type SolveResponse =
  | { type: 'progress'; value: number }
  | { type: 'found'; number: number }
  | { type: 'exhausted' }

/** 每算这么多轮回报一次进度（太频繁会刷爆 postMessage） */
const CHUNK = 500

self.onmessage = (event: MessageEvent<SolveRequest>) => {
  const { salt, challenge, max } = event.data
  let n = 0

  while (n <= max) {
    const end = Math.min(max, n + CHUNK - 1)
    for (; n <= end; n++) {
      if (sha256Hex(salt + n) === challenge) {
        ;(self as unknown as Worker).postMessage({ type: 'found', number: n } satisfies SolveResponse)
        return
      }
    }
    ;(self as unknown as Worker).postMessage({
      type: 'progress',
      value: Math.min(1, n / (max + 1)),
    } satisfies SolveResponse)
  }

  ;(self as unknown as Worker).postMessage({ type: 'exhausted' } satisfies SolveResponse)
}
