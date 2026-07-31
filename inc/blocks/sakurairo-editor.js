/**
 * Sakurairo Blocks Editor Script
 *
 * Registers Sakurairo-compatible Gutenberg blocks in the post editor.
 * All strings are inline (not minified) for maintainability.
 */
(function (wp) {
  'use strict';

  if (!wp) return;

  var blocks = wp.blocks;
  var domReady = wp.domReady;
  var hooks = wp.hooks;
  var blockEditor = wp.blockEditor;
  var components = wp.components;
  var element = wp.element;
  var i18n = wp.i18n;

  var registerBlockType = blocks.registerBlockType;
  var RichText = blockEditor.RichText;
  var PlainText = blockEditor.PlainText;
  var BlockControls = blockEditor.BlockControls;
  var InspectorControls = blockEditor.InspectorControls;
  var MediaUploadCheck = blockEditor.MediaUploadCheck;
  var MediaUpload = blockEditor.MediaUpload;
  var URLInputButton = blockEditor.URLInputButton;
  var useBlockProps = blockEditor.useBlockProps;
  var ToolbarGroup = components.ToolbarGroup;
  var ToolbarDropdownMenu = components.ToolbarDropdownMenu;
  var ToolbarButton = components.ToolbarButton;
  var TextControl = components.TextControl;
  var PanelBody = components.PanelBody;
  var ColorPicker = components.ColorPicker;
  var createElement = element.createElement;
  var Fragment = element.Fragment;
  var RawHTML = element.RawHTML;

  // ---- Locale Helper ----
  function getLocale() {
    var lang = (window.iroBlockEditor && window.iroBlockEditor.language) ||
               window.navigator.language || 'zh-CN';
    return lang.replace('_', '-');
  }

  // ---- Add "Sakurairo" category ----
  domReady(function () {
    var categories = blocks.getCategories();
    var slugs = categories.map(function (c) { return c.slug; });
    if (slugs.indexOf('sakurairo') === -1) {
      // Insert after the first category (usually "text")
      categories.splice(1, 0, { slug: 'sakurairo', title: 'Sakurairo兼容区块' });
      blocks.setCategories(categories);
    }
  });

  // ====================================================================
  // 1. Code Block — Language Selector (enhances core/code)
  // ====================================================================
  var codeLangLabels = {};
  (function () {
    var lang = getLocale();
    if (lang.indexOf('zh-CN') === 0 || lang.indexOf('zh-Hans') === 0) {
      codeLangLabels = {
        title: '高亮语言设置',
        placeholder: '此处编写代码...',
        auto: '自动识别'
      };
    } else if (lang.indexOf('zh-TW') === 0 || lang.indexOf('zh-HK') === 0 || lang.indexOf('zh-MO') === 0) {
      codeLangLabels = {
        title: '高亮語言設置',
        placeholder: '此處編寫代碼...',
        auto: '自動識別'
      };
    } else if (lang.indexOf('ja') === 0) {
      codeLangLabels = {
        title: 'シンタックスハイライト設定',
        placeholder: 'コードを入力...',
        auto: '自動識別'
      };
    } else {
      codeLangLabels = {
        title: 'Syntax Highlighting Settings',
        placeholder: 'Enter your code here...',
        auto: 'Auto Detect'
      };
    }
  })();

  var codeLangs = [
    { label: codeLangLabels.auto, value: '' },
    { label: 'HTML', value: 'html' },
    { label: 'CSS', value: 'css' },
    { label: 'JavaScript', value: 'javascript' },
    { label: 'TypeScript', value: 'typescript' },
    { label: 'PHP', value: 'php' },
    { label: 'SCSS', value: 'scss' },
    { label: 'Vue', value: 'vue' },
    { label: 'React', value: 'jsx' },
    { label: 'Python', value: 'python' },
    { label: 'Java', value: 'java' },
    { label: 'JSON', value: 'json' },
    { label: 'Dart', value: 'dart' },
    { label: 'C', value: 'c' },
    { label: 'C++', value: 'cpp' },
    { label: 'C#', value: 'csharp' },
    { label: 'Go', value: 'go' },
    { label: 'Lua', value: 'lua' },
    { label: 'Swift', value: 'swift' },
    { label: 'Kotlin', value: 'kotlin' },
    { label: 'Ruby', value: 'ruby' },
    { label: 'Rust', value: 'rust' },
    { label: 'YAML', value: 'yaml' },
    { label: 'TOML', value: 'toml' },
    { label: 'INI', value: 'ini' },
    { label: 'SQL', value: 'sql' },
    { label: 'XML', value: 'xml' },
    { label: 'Markdown', value: 'markdown' }
  ];

  function CodeLanguageEdit(props) {
    var attributes = props.attributes;
    var setAttributes = props.setAttributes;
    var content = attributes.content;
    var language = attributes.language;
    var blockProps = useBlockProps();

    var controls = codeLangs.map(function (lang) {
      return {
        title: lang.label,
        icon: false,
        onClick: function () { setAttributes({ language: lang.value }); },
        isActive: language === lang.value
      };
    });

    return createElement(Fragment, null,
      createElement(BlockControls, null,
        createElement(ToolbarGroup, null,
          createElement(ToolbarDropdownMenu, {
            label: codeLangLabels.title,
            controls: controls
          })
        )
      ),
      createElement('pre', blockProps,
        createElement('code', { className: language ? 'language-' + language : '' },
          createElement(PlainText, {
            value: content,
            onChange: function (val) { setAttributes({ content: val }); },
            placeholder: codeLangLabels.placeholder
          })
        )
      )
    );
  }

  hooks.addFilter(
    'blocks.registerBlockType',
    'sakurairo/code-language-support',
    function (settings, name) {
      if (name !== 'core/code') return settings;
      return Object.assign({}, settings, {
        attributes: Object.assign({}, settings.attributes, {
          language: { type: 'string', default: '' }
        }),
        edit: CodeLanguageEdit
      });
    }
  );

  // ====================================================================
  // 2. Notice Block (提示块)
  // ====================================================================
  var noticeLabels = {};
  (function () {
    var lang = getLocale();
    if (lang.indexOf('zh-CN') === 0 || lang.indexOf('zh-Hans') === 0) {
      noticeLabels = {
        blockTitle: '提示块',
        typeTitle: '提示类型',
        placeholder: '此处输入内容...',
        taskLabel: '任务提示',
        warningLabel: '警告提示',
        nowayLabel: '禁止提示',
        buyLabel: '允许提示'
      };
    } else if (lang.indexOf('zh-TW') === 0 || lang.indexOf('zh-HK') === 0 || lang.indexOf('zh-MO') === 0) {
      noticeLabels = {
        blockTitle: '提示區塊',
        typeTitle: '提示類型',
        placeholder: '此處輸入內容...',
        taskLabel: '任務提示',
        warningLabel: '警告提示',
        nowayLabel: '禁止提示',
        buyLabel: '允許提示'
      };
    } else if (lang.indexOf('ja') === 0) {
      noticeLabels = {
        blockTitle: 'ヒントブロック',
        typeTitle: 'ヒントタイプ',
        placeholder: 'ここに内容を入力...',
        taskLabel: 'タスク',
        warningLabel: '警告',
        nowayLabel: '禁止',
        buyLabel: '許可'
      };
    } else {
      noticeLabels = {
        blockTitle: 'Callout Block',
        typeTitle: 'Callout Type',
        placeholder: 'Enter content here...',
        taskLabel: 'Task',
        warningLabel: 'Warning',
        nowayLabel: 'Forbidden',
        buyLabel: 'Allowed'
      };
    }
  })();

  var noticeTypes = {
    task:    { label: noticeLabels.taskLabel,    icon: '<i class="ti ti-list-check"></i>', className: 'task',    titleText: 'TASK' },
    warning: { label: noticeLabels.warningLabel, icon: '<i class="ti ti-error"></i>',        className: 'warning', titleText: 'WARNING' },
    noway:   { label: noticeLabels.nowayLabel,   icon: '<i class="ti ti-x-circle"></i>',     className: 'noway',   titleText: 'DIAALLOWED' },
    buy:     { label: noticeLabels.buyLabel,     icon: '<i class="ti ti-check-circle"></i>', className: 'buy',     titleText: 'ALLOWED' }
  };

  function NoticeEdit(props) {
    var attributes = props.attributes;
    var setAttributes = props.setAttributes;
    var content = attributes.content;
    var type = attributes.type;
    var isExample = attributes.isExample;
    var blockProps = useBlockProps();
    var currentType = noticeTypes[type] || noticeTypes.task;

    if (isExample) {
      return createElement('div', null,
        createElement('div', { className: 'shortcodestyle task', style: { padding: '15px' } },
          createElement('span', { className: 'sc-title' }, noticeTypes.task.titleText),
          createElement('span', { className: 'sc-content' }, 'This is a task item')
        ),
        createElement('div', { className: 'shortcodestyle warning', style: { padding: '15px', marginTop: '8px' } },
          createElement('span', { className: 'sc-title' }, noticeTypes.warning.titleText),
          createElement('span', { className: 'sc-content' }, 'Warning content text')
        ),
        createElement('div', { className: 'shortcodestyle noway', style: { padding: '15px', marginTop: '8px' } },
          createElement('span', { className: 'sc-title' }, noticeTypes.noway.titleText),
          createElement('span', { className: 'sc-content' }, 'This is not allowed')
        ),
        createElement('div', { className: 'shortcodestyle buy', style: { padding: '15px', marginTop: '8px' } },
          createElement('span', { className: 'sc-title' }, noticeTypes.buy.titleText),
          createElement('span', { className: 'sc-content' }, 'This is allowed content')
        )
      );
    }

    var controls = [];
    for (var key in noticeTypes) {
      if (noticeTypes.hasOwnProperty(key)) {
        (function (k) {
          controls.push({
            title: noticeTypes[k].label,
            icon: false,
            onClick: function () { setAttributes({ type: k }); },
            isActive: type === k
          });
        })(key);
      }
    }

    return createElement(Fragment, null,
      createElement(BlockControls, null,
        createElement(ToolbarGroup, null,
          createElement(ToolbarDropdownMenu, {
            icon: 'admin-generic',
            label: noticeLabels.typeTitle,
            controls: controls
          })
        )
      ),
      createElement('div', Object.assign({}, blockProps, {
        className: 'shortcodestyle ' + currentType.className
      }),
        createElement('span', { className: 'sc-title' }, currentType.titleText),
        createElement(RichText, {
          tagName: 'span',
          className: 'sc-content',
          value: content,
          onChange: function (val) { setAttributes({ content: val }); },
          placeholder: noticeLabels.placeholder
        })
      )
    );
  }

  function NoticeSave(props) {
    var attributes = props.attributes;
    var content = attributes.content;
    var type = attributes.type;
    var currentType = noticeTypes[type] || noticeTypes.task;
    return createElement('div', { className: 'shortcodestyle ' + currentType.className },
      createElement('span', { className: 'sc-title' }, currentType.titleText),
      createElement(RichText.Content, { tagName: 'span', className: 'sc-content', value: content })
    );
  }

  registerBlockType('sakurairo/notice-block', {
    title: noticeLabels.blockTitle,
    icon: createElement('svg', { viewBox: '0 0 24 24', width: 24, height: 24, fill: 'none', stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round', strokeLinejoin: 'round' },
      createElement('path', { d: 'M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9' }),
      createElement('path', { d: 'M13.73 21a2 2 0 0 1-3.46 0' })
    ),
    category: 'sakurairo',
    attributes: {
      content:   { type: 'string', source: 'html', selector: '.sc-content' },
      type:      { type: 'string', default: 'task' },
      isExample: { type: 'boolean', default: false }
    },
    edit: NoticeEdit,
    save: NoticeSave,
    example: {
      attributes: { isExample: true }
    }
  });

  // ====================================================================
  // 3. ShowCard Block (展示卡片)
  // ====================================================================
  var cardLabels = {};
  (function () {
    var lang = getLocale();
    if (lang.indexOf('zh-CN') === 0 || lang.indexOf('zh-Hans') === 0) {
      cardLabels = {
        blockTitle: '展示卡片',
        toolbarButtonLabel: '选择图片',
        panelTitle: '展示卡片设置',
        iconClassLabel: 'Tabler 图标类名',
        iconClassHelp: '例如 ti ti-book',
        titleLabel: '标题',
        imgUrlLabel: '图片链接（可选）',
        iconColorLabel: '图标颜色',
        linkLabel: '跳转链接',
        iconPlaceholder: '输入图标类名...',
        titlePlaceholder: '输入卡片标题...'
      };
    } else if (lang.indexOf('zh-TW') === 0 || lang.indexOf('zh-HK') === 0 || lang.indexOf('zh-MO') === 0) {
      cardLabels = {
        blockTitle: '展示卡片',
        toolbarButtonLabel: '選擇圖片',
        panelTitle: '展示卡片設定',
        iconClassLabel: 'Tabler 圖標類名',
        iconClassHelp: '例如 ti ti-book',
        titleLabel: '標題',
        imgUrlLabel: '圖片連結（可選）',
        iconColorLabel: '圖標顏色',
        linkLabel: '跳轉連結',
        iconPlaceholder: '輸入圖標類名...',
        titlePlaceholder: '輸入卡片標題...'
      };
    } else if (lang.indexOf('ja') === 0) {
      cardLabels = {
        blockTitle: 'カード表示',
        toolbarButtonLabel: '画像を選択',
        panelTitle: 'カード表示設定',
        iconClassLabel: 'Tablerアイコンクラス',
        iconClassHelp: '例: ti ti-book',
        titleLabel: 'タイトル',
        imgUrlLabel: '画像リンク（任意）',
        iconColorLabel: 'アイコン色',
        linkLabel: 'リンク',
        iconPlaceholder: 'アイコンクラスを入力...',
        titlePlaceholder: 'カードタイトルを入力...'
      };
    } else {
      cardLabels = {
        blockTitle: 'ShowCard',
        toolbarButtonLabel: 'Select Image',
        panelTitle: 'ShowCard Settings',
        iconClassLabel: 'Tabler Icon Classes',
        iconClassHelp: 'e.g., ti ti-book',
        titleLabel: 'Title',
        imgUrlLabel: 'Image URL (Optional)',
        iconColorLabel: 'Icon Color',
        linkLabel: 'Link',
        iconPlaceholder: 'Enter icon classes...',
        titlePlaceholder: 'Enter card title...'
      };
    }
  })();

  function ShowCardEdit(props) {
    var attributes = props.attributes;
    var setAttributes = props.setAttributes;
    var icon = attributes.icon;
    var title = attributes.title;
    var img = attributes.img;
    var color = attributes.color;
    var link = attributes.link;
    var isExample = attributes.isExample;
    var blockProps = useBlockProps();

    if (isExample) {
      return createElement('img', {
        src: 'https://docs.fuukei.org/short-code/showc.png',
        alt: 'Preview',
        style: { width: '100%', height: 'auto', display: 'block' }
      });
    }

    return createElement(Fragment, null,
      createElement(BlockControls, null,
        createElement(ToolbarGroup, null,
          createElement(MediaUploadCheck, null,
            createElement(MediaUpload, {
              onSelect: function (media) { setAttributes({ img: media.url }); },
              allowedTypes: ['image'],
              render: function (obj) {
                return createElement(ToolbarButton, {
                  icon: 'format-image',
                  label: cardLabels.toolbarButtonLabel,
                  onClick: obj.open
                });
              }
            })
          )
        )
      ),
      createElement(InspectorControls, null,
        createElement(PanelBody, { title: cardLabels.panelTitle, initialOpen: true },
          createElement(TextControl, {
            label: cardLabels.iconClassLabel,
            value: icon,
            onChange: function (val) { setAttributes({ icon: val }); },
            help: cardLabels.iconClassHelp
          }),
          createElement(TextControl, {
            label: cardLabels.titleLabel,
            value: title,
            onChange: function (val) { setAttributes({ title: val }); }
          }),
          createElement(TextControl, {
            label: cardLabels.imgUrlLabel,
            value: img,
            onChange: function (val) { setAttributes({ img: val }); }
          }),
          createElement('p', null, createElement('strong', null, cardLabels.iconColorLabel)),
          createElement(ColorPicker, {
            color: color,
            onChangeComplete: function (val) { setAttributes({ color: val.hex }); },
            disableAlpha: true
          }),
          createElement('p', null, createElement('strong', null, cardLabels.linkLabel)),
          createElement(URLInputButton, {
            url: link,
            onChange: function (val) { setAttributes({ link: val }); }
          })
        )
      ),
      createElement('div', Object.assign({}, blockProps, { className: 'showcard' }),
        createElement('div', {
          className: 'img',
          style: {
            background: img ? 'url(' + img + ') center center / cover no-repeat' : '#ccc'
          }
        },
          createElement('a', { href: link },
            createElement('button', {
              className: 'showcard-button',
              style: { color: color }
            },
              createElement(RawHTML, null, '<i class="ti ti-play-circle" style="font-size:24px"></i>')
            )
          )
        ),
        createElement('div', { className: 'icon-title' },
          createElement(RawHTML, null, '<i class="' + icon + '" style="color:' + color + ' !important;"></i>'),
          createElement('span', { className: 'title' }, title)
        )
      )
    );
  }

  function ShowCardSave(props) {
    var attributes = props.attributes;
    var icon = attributes.icon;
    var title = attributes.title;
    var img = attributes.img;
    var color = attributes.color;
    var link = attributes.link;
    return createElement('div', { className: 'showcard' },
      createElement('div', {
        className: 'img',
        style: { background: 'url(' + img + ') center center / cover no-repeat' }
      },
        createElement('a', { href: link },
          createElement('button', {
            className: 'showcard-button',
            style: { color: color + ' !important' }
          },
            createElement(RawHTML, null, '<i class="ti ti-play-circle" style="font-size:24px"></i>')
          )
        )
      ),
      createElement('div', { className: 'icon-title' },
        createElement(RawHTML, null, '<i class="ti ti-bookmark" style="color:' + color + ';font-size:16px"></i>'),
        createElement('span', { className: 'title' }, title)
      )
    );
  }

  registerBlockType('sakurairo/showcard-block', {
    title: cardLabels.blockTitle,
    icon: createElement('svg', { viewBox: '0 0 24 24', width: 24, height: 24, fill: 'none', stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round', strokeLinejoin: 'round' },
      createElement('rect', { x: 2, y: 4, width: 20, height: 16, rx: 2 }),
      createElement('circle', { cx: 9, cy: 11, r: 2 }),
      createElement('path', { d: 'M14 15c0-1.5-2-3-5-3s-5 1.5-5 3' })
    ),
    category: 'sakurairo',
    attributes: {
      icon:      { type: 'string', default: 'ti ti-bookmark' },
      title:     { type: 'string', default: cardLabels.titlePlaceholder },
      img:       { type: 'string', default: '' },
      color:     { type: 'string', default: '#ffffff' },
      link:      { type: 'string', default: '' },
      isExample: { type: 'boolean', default: false }
    },
    edit: ShowCardEdit,
    save: ShowCardSave,
    example: {
      attributes: { isExample: true }
    }
  });

  // ====================================================================
  // 4. Conversations Block (对话块)
  // ====================================================================
  var convLabels = {};
  (function () {
    var lang = getLocale();
    if (lang.indexOf('zh-CN') === 0 || lang.indexOf('zh-Hans') === 0) {
      convLabels = {
        blockTitle: '对话块',
        imageLabel: '设置头像',
        directionLabel: '切换方向',
        placeholder: '请输入对话内容…'
      };
    } else if (lang.indexOf('zh-TW') === 0 || lang.indexOf('zh-HK') === 0 || lang.indexOf('zh-MO') === 0) {
      convLabels = {
        blockTitle: '對話區塊',
        imageLabel: '設定大頭貼',
        directionLabel: '切換方向',
        placeholder: '請輸入對話內容…'
      };
    } else if (lang.indexOf('ja') === 0) {
      convLabels = {
        blockTitle: '会話ブロック',
        imageLabel: 'アバター設定',
        directionLabel: '方向切替',
        placeholder: 'ここに会話内容を入力…'
      };
    } else {
      convLabels = {
        blockTitle: 'Conversations Block',
        imageLabel: 'Set Avatar',
        directionLabel: 'Toggle Direction',
        placeholder: 'Enter conversation text…'
      };
    }
  })();

  function ConversationsEdit(props) {
    var attributes = props.attributes;
    var setAttributes = props.setAttributes;
    var avatar = attributes.avatar;
    var direction = attributes.direction;
    var content = attributes.content;
    var isExample = attributes.isExample;
    var blockProps = useBlockProps();

    if (isExample) {
      return createElement('img', {
        src: 'https://docs.fuukei.org/short-code/dis.png',
        alt: 'Preview',
        style: { width: '100%', height: 'auto', display: 'block' }
      });
    }

    return createElement(Fragment, null,
      createElement(BlockControls, null,
        createElement(ToolbarGroup, null,
          createElement(MediaUploadCheck, null,
            createElement(MediaUpload, {
              onSelect: function (media) { setAttributes({ avatar: media.url }); },
              allowedTypes: ['image'],
              value: avatar,
              render: function (obj) {
                return createElement(ToolbarButton, {
                  icon: 'format-image',
                  label: convLabels.imageLabel,
                  onClick: obj.open
                });
              }
            })
          ),
          createElement(ToolbarButton, {
            icon: direction === 'row' ? 'arrow-right-alt' : 'arrow-left-alt',
            label: convLabels.directionLabel,
            onClick: function () {
              setAttributes({ direction: direction === 'row' ? 'row-reverse' : 'row' });
            }
          })
        )
      ),
      createElement('div', Object.assign({}, blockProps, {
        className: 'conversations-code',
        style: { display: 'flex', flexDirection: direction }
      }),
        avatar
          ? createElement('img', { src: avatar, alt: '' })
          : createElement(TextControl, {
              placeholder: convLabels.imageLabel + ' URL…',
              value: avatar,
              onChange: function (val) { setAttributes({ avatar: val }); }
            }),
        createElement(RichText, {
          tagName: 'div',
          className: 'conversations-code-text',
          placeholder: convLabels.placeholder,
          value: content,
          onChange: function (val) { setAttributes({ content: val }); }
        })
      )
    );
  }

  function ConversationsSave(props) {
    var attributes = props.attributes;
    var avatar = attributes.avatar;
    var direction = attributes.direction;
    var content = attributes.content;
    return createElement('div', {
      className: 'conversations-code',
      style: { display: 'flex', flexDirection: direction }
    },
      avatar && createElement('img', { src: avatar, alt: '' }),
      createElement('div', {
        className: 'conversations-code-text',
        dangerouslySetInnerHTML: { __html: content }
      })
    );
  }

  registerBlockType('sakurairo/conversations-block', {
    title: convLabels.blockTitle,
    icon: createElement('svg', { viewBox: '0 0 24 24', width: 24, height: 24, fill: 'none', stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round', strokeLinejoin: 'round' },
      createElement('path', { d: 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z' }),
      createElement('circle', { cx: 9, cy: 10, r: 1, fill: 'currentColor', stroke: 'none' }),
      createElement('circle', { cx: 12, cy: 10, r: 1, fill: 'currentColor', stroke: 'none' }),
      createElement('circle', { cx: 15, cy: 10, r: 1, fill: 'currentColor', stroke: 'none' })
    ),
    category: 'sakurairo',
    attributes: {
      avatar:    { type: 'string', default: '' },
      direction: { type: 'string', default: 'row' },
      content:   { type: 'string', source: 'html', selector: '.conversations-code-text' },
      isExample: { type: 'boolean', default: false }
    },
    edit: ConversationsEdit,
    save: ConversationsSave,
    example: {
      attributes: { isExample: true }
    }
  });

  // ====================================================================
  // 5. Bilibili Video Block
  // ====================================================================
  var biliLabels = {};
  (function () {
    var lang = getLocale();
    if (lang.indexOf('zh-CN') === 0 || lang.indexOf('zh-Hans') === 0) {
      biliLabels = {
        blockTitle: 'Bilibili 视频',
        placeholder: '请输入 Bilibili 视频 ID（如 BV1xx、av123456）',
        label: '视频 ID',
        error: '无效的视频 ID，请输入 BV 或 av 编号。'
      };
    } else if (lang.indexOf('zh-TW') === 0 || lang.indexOf('zh-HK') === 0 || lang.indexOf('zh-MO') === 0) {
      biliLabels = {
        blockTitle: 'Bilibili 視頻',
        placeholder: '請輸入 Bilibili 視頻 ID（例如 BV1xx、av123456）',
        label: '視頻 ID',
        error: '無效的視頻 ID，請輸入 BV 或 av 編號。'
      };
    } else if (lang.indexOf('ja') === 0) {
      biliLabels = {
        blockTitle: 'Bilibili ビデオ',
        placeholder: 'Bilibiliの動画ID（例：BV1xx、av123456）を入力してください',
        label: '動画 ID',
        error: '無効な動画IDです。BVまたはav形式で入力してください。'
      };
    } else {
      biliLabels = {
        blockTitle: 'Bilibili Video',
        placeholder: 'Enter Bilibili Video ID (e.g. BV1xx or av123456)',
        label: 'Video ID',
        error: 'Invalid video ID. Please enter BV or av format.'
      };
    }
  })();

  function buildBilibiliUrl(videoId) {
    var id = (videoId || '').trim();
    if (/^av\d+$/i.test(id)) {
      return 'https://player.bilibili.com/player.html?avid=' + id.replace(/^av/i, '') + '&page=1&autoplay=0&danmaku=0';
    }
    if (/^BV[a-zA-Z0-9]+$/.test(id)) {
      return 'https://player.bilibili.com/player.html?bvid=' + id + '&page=1&autoplay=0&danmaku=0';
    }
    return '';
  }

  function BilibiliEdit(props) {
    var attributes = props.attributes;
    var setAttributes = props.setAttributes;
    var videoId = attributes.videoId;
    var isExample = attributes.isExample;
    var blockProps = useBlockProps();

    if (isExample) {
      return createElement('img', {
        src: 'https://docs.fuukei.org/short-code/bvcode.png',
        alt: 'Preview',
        style: { width: '100%', height: 'auto', display: 'block' }
      });
    }

    var src = buildBilibiliUrl(videoId);
    var hasError = videoId && !src;

    return createElement(Fragment, null,
      createElement(InspectorControls, null,
        createElement(PanelBody, { title: biliLabels.label },
          createElement(TextControl, {
            label: biliLabels.label,
            value: videoId,
            onChange: function (val) { setAttributes({ videoId: val }); },
            placeholder: biliLabels.placeholder
          })
        )
      ),
      createElement('div', blockProps,
        src
          ? createElement('div', {
              style: { position: 'relative', padding: '56.25% 0 0 0' }
            },
              createElement('iframe', {
                src: src,
                sandbox: 'allow-top-navigation allow-same-origin allow-forms allow-scripts',
                allowFullScreen: true,
                style: {
                  pointerEvents: 'none',
                  position: 'absolute',
                  width: '100%', height: '100%',
                  left: 0, top: 0, border: 'none', overflow: 'hidden'
                }
              })
            )
          : createElement(TextControl, {
              label: biliLabels.label,
              value: videoId,
              onChange: function (val) { setAttributes({ videoId: val }); },
              placeholder: biliLabels.placeholder,
              help: hasError ? biliLabels.error : ''
            })
      )
    );
  }

  function BilibiliSave(props) {
    var videoId = props.attributes.videoId;
    var src = buildBilibiliUrl(videoId);
    if (!src) return null;
    return createElement('div', {
      className: 'vbilibili',
      style: { position: 'relative', padding: '56.25% 0 0 0' }
    },
      createElement('iframe', {
        src: src,
        sandbox: 'allow-top-navigation allow-same-origin allow-forms allow-scripts',
        allowFullScreen: true,
        style: {
          position: 'absolute',
          width: '100%', height: '100%',
          left: 0, top: 0, border: 'none', overflow: 'hidden'
        }
      })
    );
  }

  registerBlockType('sakurairo/vbilibili', {
    title: biliLabels.blockTitle,
    icon: createElement('svg', { viewBox: '0 0 24 24', width: 24, height: 24, fill: 'none', stroke: 'currentColor', strokeWidth: 2, strokeLinecap: 'round', strokeLinejoin: 'round' },
      createElement('polygon', { points: '5 3 19 12 5 21 5 3' })
    ),
    category: 'sakurairo',
    supports: { html: false },
    attributes: {
      videoId:   { type: 'string' },
      isExample: { type: 'boolean', default: false }
    },
    edit: BilibiliEdit,
    save: BilibiliSave,
    example: {
      attributes: { videoId: '', isExample: true }
    }
  });

})(window.wp);
