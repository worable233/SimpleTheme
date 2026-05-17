import { existsSync, readdirSync } from 'node:fs'
import { resolve, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = fileURLToPath(new URL('.', import.meta.url))
const dir = resolve(__dirname, '..', 'public', 'illustrations')

if (!existsSync(dir)) {
  console.log('DIR NOT FOUND')
} else {
  const files = readdirSync(dir)
  console.log('Files:', files.join(', '))
}
