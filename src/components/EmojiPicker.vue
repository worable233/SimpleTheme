<script setup lang="ts">
/**
 * EmojiPicker — 表情选择面板（bilibili / tieba / 颜文字）
 */
import {
  bilibiliNames,
  biliImg,
  tiebaNames,
  tiebaImg,
  dinosaurNames,
  dinoImg,
  emojiBase,
} from '@/lib/emoji'

defineOptions({ name: 'EmojiPicker' })

const emit = defineEmits<{
  (e: 'select', text: string): void
}>()

const emojiTab = defineModel<'bilibili' | 'tieba' | 'dinosaur' | 'kaomoji'>('tab', { default: 'bilibili' })

// 颜文字 (来自 Sakurairo)
const kaomojiList = [
  '(⌒▽⌒)', '（￣▽￣）', '(=・ω・=)', '(｀・ω・´)', '(〜￣△￣)〜',
  '(･∀･)', '(°∀°)ﾉ', '(￣3￣)', '╮(￣▽￣)╭', '(´_ゝ｀)',
  '←_←', '→_→', '(<_<)', '(>_>)', '(;¬_¬)',
  '("▔□▔)/', '(ﾟДﾟ≡ﾟдﾟ)!?', 'Σ(ﾟдﾟ;)', 'Σ(￣□￣||)', '(’；ω；‘)',
  '（/TДT)/', '(^・ω・^ )', '(｡･ω･｡)', '(●￣(ｴ)￣●)', 'ε=ε=(ノ≧∇≦)ノ',
  '(’･_･‘)', '(-_-#)', '（￣へ￣）', '(￣ε(#￣)Σ', "ヽ('Д')ﾉ",
  '（#-_-)┯━┯', '(╯°口°)╯(┴—┴', '←◡←', '( ♥д♥)', '_(:3」∠)_',
  'Σ>―(〃°ω°〃)♡→', '⁄(⁄ ⁄•⁄ω⁄•⁄ ⁄)⁄', '(╬ﾟдﾟ)▄︻┻┳═一', '･*･:≡(　ε:)',
  '(笑)', '(汗)', '(泣)', '(苦笑)',
]
</script>

<template>
  <div class="comments-emoji" @click.stop>
    <div class="comments-emoji__tabs">
      <button
        class="comments-emoji__tab"
        :class="{ 'comments-emoji__tab--active': emojiTab === 'bilibili' }"
        type="button"
        @click="emojiTab = 'bilibili'"
      >
        <img
          class="comments-emoji__tab-icon"
          :src="emojiBase() + 'bili/emoji_keai.webp'"
          alt="bilibili"
        />
        bilibili
      </button>
      <button
        class="comments-emoji__tab"
        :class="{ 'comments-emoji__tab--active': emojiTab === 'tieba' }"
        type="button"
        @click="emojiTab = 'tieba'"
      >
        <img
          class="comments-emoji__tab-icon"
          :src="emojiBase() + 'tieba/icon_haha.webp'"
          alt="tieba"
        />
        Tieba
      </button>
      <button
        class="comments-emoji__tab"
        :class="{ 'comments-emoji__tab--active': emojiTab === 'dinosaur' }"
        type="button"
        @click="emojiTab = 'dinosaur'"
      >
        <img
          class="comments-emoji__tab-icon"
          :src="dinoImg('dinosaur-shy')"
          alt="dinosaur"
        />
        Dinosaur
      </button>
      <button
        class="comments-emoji__tab"
        :class="{ 'comments-emoji__tab--active': emojiTab === 'kaomoji' }"
        type="button"
        @click="emojiTab = 'kaomoji'"
      >
        (･∀･) 颜文字
      </button>
    </div>

    <!-- Bilibili -->
    <div v-if="emojiTab === 'bilibili'" class="comments-emoji__grid">
      <button
        v-for="name in bilibiliNames"
        :key="name"
        class="comments-emoji__item comments-emoji__item--img"
        type="button"
        @mousedown.prevent
        @click="emit('select', '{{' + name + '}}')"
        :title="name"
      >
        <img :src="biliImg(name)" :alt="name" loading="lazy" />
      </button>
    </div>

    <!-- Tieba -->
    <div v-if="emojiTab === 'tieba'" class="comments-emoji__grid">
      <button
        v-for="name in tiebaNames"
        :key="name"
        class="comments-emoji__item comments-emoji__item--img"
        type="button"
        @mousedown.prevent
        @click="emit('select', '::' + name + '::')"
        :title="name"
      >
        <img :src="tiebaImg(name)" :alt="name" loading="lazy" />
      </button>
    </div>

    <!-- 小恐龙 -->
    <div v-if="emojiTab === 'dinosaur'" class="comments-emoji__grid">
      <button
        v-for="name in dinosaurNames"
        :key="name"
        class="comments-emoji__item comments-emoji__item--img"
        type="button"
        @mousedown.prevent
        @click="emit('select', '#' + name + '#')"
        :title="name"
      >
        <img :src="dinoImg(name)" :alt="name" loading="lazy" />
      </button>
    </div>

    <!-- 颜文字 -->
    <div v-if="emojiTab === 'kaomoji'" class="comments-emoji__grid">
      <button
        v-for="e in kaomojiList"
        :key="e"
        class="comments-emoji__item comments-emoji__item--kaomoji"
        type="button"
        @mousedown.prevent
        @click="emit('select', e)"
      >
        {{ e }}
      </button>
    </div>
  </div>
</template>
