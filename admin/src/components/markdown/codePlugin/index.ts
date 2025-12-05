import type { PluginWithOptions } from 'markdown-it'

import { resolveLanguage } from './resolveLanguage.js'
import { resolveLineNumbers } from './resolveLineNumbers.js'

export interface CodePluginOptions {
  lineNumbers?: boolean | number
}

export const codePlugin: PluginWithOptions<CodePluginOptions> = (
  md,
  { lineNumbers = true } = {}
): void => {
  md.renderer.rules.fence = (tokens, idx, options, env, slf) => {
    const token = tokens[idx]
    const info = token.info ? md.utils.unescapeAll(token.info).trim() : ''
    const language = resolveLanguage(info)
    const languageClass = `${options.langPrefix}${language.name}`

    const code =
      options.highlight?.(token.content, language.name, '') ||
      md.utils.escapeHtml(token.content)

    token.attrJoin('class', languageClass)
    let result = code.startsWith('<pre')
      ? code
      : `<pre${slf.renderAttrs(token)}><code>${code}</code></pre>`
    result = `<div  data-ext="${language.ext}" v-pre class="code-copy-line">
      <div class="code-copy-btn">复制代码</div>
    </div>${result}`
    const lines = code.split('\n').slice(0, -1)

    const useLineNumbers =
      resolveLineNumbers(info) ??
      (typeof lineNumbers === 'number'
        ? lines.length >= lineNumbers
        : lineNumbers)
    if (useLineNumbers) {
      const lineNumbersCode = lines
        .map(() => `<div class="line-number"></div>`)
        .join('')

      result = `${result}<div class="line-numbers" aria-hidden="true">${lineNumbersCode}</div>`
    }

    result = `<div><div class="${languageClass}${
      useLineNumbers ? ' line-numbers-mode' : ''
    }"><div class="pre-code-scroll">${result}</div></div></div>`

    return result
  }
}
