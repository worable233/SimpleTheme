/** 表情渲染工具 — 来自 Sakurairo 主题的 emoji 数据 */

const CDN_BASE = 'https://s.nmxc.ltd/sakurairo_vision/@3.0/smilies/'

// Bilibili 表情 (50个)
const bilibiliNames = [
  'baiyan', 'bishi', 'bizui', 'chan', 'dai', 'daku', 'dalao', 'dalian',
  'dianzan', 'doge', 'facai', 'fanu', 'ganga', 'guilian', 'guzhang',
  'haixiu', 'heirenwenhao', 'huaixiao', 'jingxia', 'keai', 'koubizi',
  'kun', 'lengmo', 'liubixue', 'liuhan', 'liulei', 'miantian',
  'mudengkoudai', 'nanguo', 'outu', 'qinqin', 'se', 'shengbing',
  'shengqi', 'shuizhao', 'sikao', 'tiaokan', 'tiaopi', 'touxiao',
  'tuxue', 'weiqu', 'weixiao', 'wunai', 'xiaoku', 'xieyanxiao',
  'yiwen', 'yun', 'zaijian', 'zhoumei', 'zhuakuang',
]

// 贴吧表情 (32个)
const tiebaNames = [
  'good', 'han', 'spray', 'Grievance', 'shui', 'reluctantly', 'anger',
  'tongue', 'se', 'haha', 'rmb', 'doubt', 'tear', 'surprised2', 'Happy',
  'ku', 'surprised', 'theblackline', 'smilingeyes', 'spit', 'huaji',
  'bbd', 'hu', 'shame', 'naive', 'rbq', 'britan', 'aa', 'niconiconi',
  'niconiconi_t', 'niconiconit', 'awesome',
]

function biliImg(name: string) {
  return `${CDN_BASE}bilipng/emoji_${name}.png`
}

function tiebaImg(name: string) {
  return `${CDN_BASE}tiebapng/icon_${name}.png`
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
  return result
}

/** 将纯文本中的表情标记替换为表情名称（用于提取纯文本，反向操作） */
export function getEmojiNames(): { isBili: boolean; name: string }[] {
  return [
    ...bilibiliNames.map((name) => ({ isBili: true, name })),
    ...tiebaNames.map((name) => ({ isBili: false, name })),
  ]
}

export { CDN_BASE, bilibiliNames, tiebaNames, biliImg, tiebaImg }
