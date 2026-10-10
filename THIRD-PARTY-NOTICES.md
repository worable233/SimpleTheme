# 第三方组件与素材声明 / Third-Party Notices

Simple Theme 自身以 [MIT](./LICENSE) 许可发布。本项目在构建产物中分发下列第三方
组件与素材，各自的版权与许可如下。

## 随发行包分发的开源组件（MIT）

以下组件均以 **MIT License** 授权，版权归各自作者所有。

| 组件 | 用途 | 版权 |
| --- | --- | --- |
| [Parsedown](https://github.com/erusev/parsedown) | `inc/lib/Parsedown.php` — 服务端 Markdown 解析 | © 2013-2018 Emanuil Rusev, erusev.com |
| [PrismJS](https://github.com/PrismJS/prism) | `dist/prism/*.min.js` — 代码高亮 | © 2012 Lea Verou |
| [unDraw](https://undraw.co/) / [undraw-svg](https://www.npmjs.com/package/undraw-svg) | `dist/illustrations/*.svg` — 插画 | unDraw 插画 © Katerina Limpitsouni（unDraw 开放许可，免费使用，建议署名） |
| [Tabler Icons](https://github.com/tabler/tabler-icons) | 界面图标（构建时打包进 JS） | © 2020-2024 Paweł Kuna |
| [Reka UI](https://github.com/unovue/reka-ui) | `StSelect` / `StDrawer` 等无头组件底层 | © 2023 Reka UI |
| [Morphicons](https://github.com/guillermolg00/morphicons) | 图标形态动效 | © Morphicons |
| [Altcha](https://github.com/altcha-org/altcha) | 表单反机器人校验 | © Altcha |

### MIT License（适用于上表中所有组件）

```
Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

## Sakurairo 区块兼容层（GPL-2.0）

`inc/blocks/sakurairo.php`、`inc/blocks/notice-block.js`、`inc/blocks/notice-block.css`
为兼容 [Sakurairo](https://github.com/mirai-mamori/Sakurairo) 主题已保存内容而
移植/改写，Sakurairo 以 **GPL-2.0** 发布，相关文件版权归其作者 © mirai-mamori
及贡献者，并继续以 GPL-2.0 授权。这些文件已单独标注许可来源。

GPL-2.0 全文：https://www.gnu.org/licenses/old-licenses/gpl-2.0.html

## 表情包素材

`emojis/`（bili / tieba / dinosaur 等，随构建产物 `dist/emojis/` 分发）**来源于网络**，
版权归原作者所有，仅供参考与个人使用。如权利人要求，将及时移除。

## 商业许可组件（使用者需自行确认）

[Fancybox](https://fancyapps.com/)（`@fancyapps/ui`，用于文章图片灯箱）是**商业软件**，
需遵循其 License Agreement（https://fancyapps.com/pricing）。本项目源代码不包含其
实现细节，仅在构建产物中引用，使用者的实际使用需自行确保符合其授权档位。
