/** 表情渲染工具 — 来自 Sakurairo 主题的 emoji 数据 */
import { getThemeConfig } from '@/lib/theme-config'

export function emojiBase(): string {
  if (import.meta.env.DEV) {
    return '/emojis/'
  }
  const themeUrl = getThemeConfig().themeUrl.replace(/\/+$/, '')
  return `${themeUrl}/emojis/`
}

// Bilibili 表情 (50个)
const bilibiliNames = Object.freeze([
  'baiyan', 'bishi', 'bizui', 'chan', 'dai', 'daku', 'dalao', 'dalian',
  'dianzan', 'doge', 'facai', 'fanu', 'ganga', 'guilian', 'guzhang',
  'haixiu', 'heirenwenhao', 'huaixiao', 'jingxia', 'keai', 'koubizi',
  'kun', 'lengmo', 'liubixue', 'liuhan', 'liulei', 'miantian',
  'mudengkoudai', 'nanguo', 'outu', 'qinqin', 'se', 'shengbing',
  'shengqi', 'shuizhao', 'sikao', 'tiaokan', 'tiaopi', 'touxiao',
  'tuxue', 'weiqu', 'weixiao', 'wunai', 'xiaoku', 'xieyanxiao',
  'yiwen', 'yun', 'zaijian', 'zhoumei', 'zhuakuang',
])
const biliSet = Object.freeze(new Set(bilibiliNames))

// 小恐龙表情 (16个) — 来自 Argon Theme
const dinosaurNames = Object.freeze([
  'dinosaur-shy', 'dinosaur-daze', 'dinosaur-sweat', 'dinosaur-proud',
  'dinosaur-powerless', 'dinosaur-pouting', 'dinosaur-eating', 'dinosaur-ok',
  'dinosaur-doubt', 'dinosaur-depressed', 'dinosaur-close-eyes', 'dinosaur-sleeping',
  'dinosaur-puzzled', 'dinosaur-agree', 'dinosaur-crazy', 'dinosaur-angry',
])
const dinoSet = Object.freeze(new Set(dinosaurNames))

// 贴吧表情 (32个)
const tiebaNames = Object.freeze([
  'good', 'han', 'spray', 'Grievance', 'shui', 'reluctantly', 'anger',
  'tongue', 'se', 'haha', 'rmb', 'doubt', 'tear', 'surprised2', 'Happy',
  'ku', 'surprised', 'theblackline', 'smilingeyes', 'spit', 'huaji',
  'bbd', 'hu', 'shame', 'naive', 'rbq', 'britan', 'aa', 'niconiconi',
  'niconiconi_t', 'niconiconit', 'awesome',
  ])
const tiebaSet = Object.freeze(new Set(tiebaNames))

function biliImg(name: string) {
  return `${emojiBase()}bili/emoji_${name}.png`
}

function tiebaImg(name: string) {
  return `${emojiBase()}tieba/icon_${name}.png`
}

function dinoImg(name: string) {
  const num = dinosaurNames.indexOf(name) + 1
  return `${emojiBase()}dinosaur/${num}.jpg`
}

/** 将纯文本中的表情标记渲染为 <img> HTML */
export function renderToHtml(text: string): string {
  const biliRe = /\{\{(\w+)\}\}/g
  const tiebaRe = /::(\w+)::/g
  let result = text
  // 先渲染 B站 ({{name}})
  result = result.replace(biliRe, (m, name: string) => {
    if (bilibiliNames.includes(name)) {
      return `<img draggable="false" data-type="bili" data-name="${name}" src="${biliImg(name)}" class="emoji-inline" alt="${m}">`
    }
    return m
  })
  // 再渲染贴吧 (::name::)
  result = result.replace(tiebaRe, (m, name: string) => {
    if (tiebaNames.includes(name)) {
      return `<img draggable="false" data-type="tieba" data-name="${name}" src="${tiebaImg(name)}" class="emoji-inline" alt="${m}">`
    }
    return m
  })
  // 最后渲染小恐龙 (#name#)
  const dinoRe = /#([\w-]+)#/g
  result = result.replace(dinoRe, (m, name: string) => {
    if (dinosaurNames.includes(name)) {
      return `<img draggable="false" data-type="dinosaur" data-name="${name}" src="${dinoImg(name)}" class="emoji-inline" alt="${m}">`
    }
    return m
  })
  return result
}

/** 将纯文本中的表情标记替换为表情名称（用于提取纯文本，反向操作） */
export function getEmojiNames(): { isBili: boolean; name: string }[] {
  return [
    ...bilibiliNames.map((name) => ({ isBili: true, name })),
    ...tiebaNames.map((name) => ({ isBili: false, name })),
    ...dinosaurNames.map((name) => ({ isBili: false, name })),
  ]
}

export { bilibiliNames, tiebaNames, dinosaurNames, biliImg, tiebaImg, dinoImg }
