(function (wp) {
  var registerBlockType = wp.blocks.registerBlockType;
  var RichText = wp.blockEditor.RichText;
  var BlockControls = wp.blockEditor.BlockControls;
  var useBlockProps = wp.blockEditor.useBlockProps;
  var ToolbarGroup = wp.components.ToolbarGroup;
  var ToolbarDropdownMenu = wp.components.ToolbarDropdownMenu;
  var RawHTML = wp.element.RawHTML;
  var createElement = wp.element.createElement;
  var Fragment = wp.element.Fragment;

  var labels = {};
  var lang = (window.iroBlockEditor && window.iroBlockEditor.language) || window.navigator.language || 'zh-CN';
  lang = lang.replace('_', '-');

  if (lang.indexOf('zh-CN') === 0 || lang.indexOf('zh-Hans') === 0) {
    labels = {
      blockTitle: '提示块',
      typeTitle: '提示类型',
      typeLabel: '类型',
      placeholder: '此处输入内容...',
      taskLabel: '任务提示',
      warningLabel: '警告提示',
      nowayLabel: '禁止提示',
      buyLabel: '允许提示'
    };
  } else if (lang.indexOf('zh-TW') === 0 || lang.indexOf('zh-HK') === 0 || lang.indexOf('zh-MO') === 0) {
    labels = {
      blockTitle: '提示區塊',
      typeTitle: '提示類型',
      typeLabel: '類型',
      placeholder: '此處輸入內容...',
      taskLabel: '任務提示',
      warningLabel: '警告提示',
      nowayLabel: '禁止提示',
      buyLabel: '允許提示'
    };
  } else {
    labels = {
      blockTitle: 'Callout Block',
      typeTitle: 'Callout Type',
      typeLabel: 'Type',
      placeholder: 'Enter content here...',
      taskLabel: 'Task',
      warningLabel: 'Warning',
      nowayLabel: 'Forbidden',
      buyLabel: 'Allowed'
    };
  }

  var typeConfig = {
    task: {
      label: labels.taskLabel,
      icon: '<i class="bx bx-list-check"></i>',
      className: 'task'
    },
    warning: {
      label: labels.warningLabel,
      icon: '<i class="bx bx-error"></i>',
      className: 'warning'
    },
    noway: {
      label: labels.nowayLabel,
      icon: '<i class="bx bx-x-circle"></i>',
      className: 'noway'
    },
    buy: {
      label: labels.buyLabel,
      icon: '<i class="bx bx-check-circle"></i>',
      className: 'buy'
    }
  };

  registerBlockType('sakurairo/notice-block', {
    title: labels.blockTitle,
    icon: 'format-status',
    category: 'sakurairo',
    attributes: {
      content: {
        type: 'string',
        source: 'html',
        selector: 'span'
      },
      type: {
        type: 'string',
        default: 'task'
      },
      isExample: {
        type: 'boolean',
        default: false
      }
    },
    edit: function (props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;
      var content = attributes.content;
      var type = attributes.type;
      var isExample = attributes.isExample;

      if (isExample) {
        return createElement('div', { style: { padding: '20px 0' } },
          createElement('div', {
            className: 'shortcodestyle ' + typeConfig[type].className,
            style: { padding: '15px 15px 15px 30px' }
          },
            createElement(RawHTML, null, typeConfig[type].icon),
            createElement('span', null, '示例文本')
          )
        );
      }

      var blockProps = useBlockProps();
      var currentType = typeConfig[type];
      var controls = [];

      for (var key in typeConfig) {
        if (typeConfig.hasOwnProperty(key)) {
          (function (k) {
            controls.push({
              title: typeConfig[k].label,
              icon: false,
              onClick: function () {
                setAttributes({ type: k });
              },
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
              label: labels.typeTitle,
              controls: controls
            })
          )
        ),
        createElement('div', Object.assign({}, blockProps, {
          className: 'shortcodestyle ' + currentType.className
        }),
          createElement(RawHTML, null, currentType.icon),
          createElement(RichText, {
            tagName: 'span',
            value: content,
            onChange: function (value) {
              setAttributes({ content: value });
            },
            placeholder: labels.placeholder
          })
        )
      );
    },
    save: function (props) {
      var content = props.attributes.content;
      var type = props.attributes.type;
      var currentType = typeConfig[type];

      return createElement('div', {
        className: 'shortcodestyle ' + currentType.className
      },
        createElement(RawHTML, null, currentType.icon),
        createElement(RichText.Content, {
          tagName: 'span',
          value: content
        })
      );
    }
  });
})(window.wp);
