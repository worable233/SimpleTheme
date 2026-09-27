<script setup lang="ts">
import { StCard, StFormItem, StInput, StSelect, StStack, StSwitch } from '@/ui'
import { useAdminFields } from '../useAdminFields'
import type { AdminSettings } from '../api'

const props = defineProps<{
  settings: AdminSettings
  defaults: AdminSettings
}>()

const emit = defineEmits<{
  (e: 'update', key: string, value: unknown): void
}>()

const fields = useAdminFields(
  () => props.settings,
  () => props.defaults,
)

const copyrightOptions = [
  { value: 'detailed', label: '详细 — Copyright © 2026 站点名称 All Rights Reserved.' },
  { value: 'simple', label: '简洁 — 2026 © 站点名称.' },
  { value: 'none', label: '不显示' },
]

const licenseOptions = [
  { value: 'cc-by-40', label: 'CC BY 4.0 — 署名' },
  { value: 'cc-by-sa-40', label: 'CC BY-SA 4.0 — 署名-相同方式共享' },
  { value: 'cc-by-nc-40', label: 'CC BY-NC 4.0 — 署名-非商业使用' },
  { value: 'cc-by-nc-sa-40', label: 'CC BY-NC-SA 4.0 — 署名-非商业使用-相同方式共享' },
  { value: 'cc-by-nc-nd-40', label: 'CC BY-NC-ND 4.0 — 署名-非商业使用-禁止演绎' },
  { value: 'cc-by-nd-40', label: 'CC BY-ND 4.0 — 署名-禁止演绎' },
  { value: 'cc0-10', label: 'CC0 1.0 — 公共领域' },
  { value: 'mit', label: 'MIT — 开源许可' },
  { value: 'arr', label: 'ARR — 保留所有权利' },
  { value: 'none', label: '不显示' },
]
</script>

<template>
  <StStack :gap="5">
    <StCard title="版权信息" subtitle="页脚站点版权的详细程度与主题署名开关。">
      <StStack :gap="4">
        <StFormItem label="版权信息样式">
          <StSelect
            :model-value="fields.text('copyright_style', 'detailed')"
            :options="copyrightOptions"
            aria-label="版权信息样式"
            @update:model-value="emit('update', 'copyright_style', $event)"
          />
        </StFormItem>
        <StSwitch
          :model-value="fields.checked('show_theme_credit', true)"
          @update:model-value="emit('update', 'show_theme_credit', $event)"
        >
          显示主题版权信息
        </StSwitch>
      </StStack>
    </StCard>

    <StCard title="文章许可" subtitle="文章页底部展示的版权许可协议。">
      <StFormItem label="许可协议">
        <StSelect
          :model-value="fields.text('article_license', 'cc-by-nc-sa-40')"
          :options="licenseOptions"
          aria-label="文章许可协议"
          @update:model-value="emit('update', 'article_license', $event)"
        />
      </StFormItem>
    </StCard>

    <StCard title="页脚与备案" subtitle="显示在文章列表与评论区末尾的寄语，以及备案号。">
      <StStack :gap="4">
        <StFormItem label="底部寄语">
          <StInput
            :model-value="fields.text('end_note')"
            placeholder="好像就这么多"
            aria-label="底部寄语"
            @update:model-value="emit('update', 'end_note', $event)"
          />
        </StFormItem>
        <StFormItem label="ICP 备案号">
          <StInput
            :model-value="fields.text('icp_text')"
            placeholder="京ICP备2021000000号-1"
            aria-label="ICP 备案号"
            @update:model-value="emit('update', 'icp_text', $event)"
          />
        </StFormItem>
        <StFormItem label="公安备案号">
          <StInput
            :model-value="fields.text('icp_gov_text')"
            placeholder="京公网安备 11010802000001号"
            aria-label="公安备案号"
            @update:model-value="emit('update', 'icp_gov_text', $event)"
          />
        </StFormItem>
      </StStack>
    </StCard>
  </StStack>
</template>
