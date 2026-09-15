import { rewritePlayVideoLines } from '@/api/play_video'
import { MAX_LINES, SPEECH_RUNES_PER_SECOND, MAX_SPEECH_SECONDS } from '../enums/play_video'
import {
    linesFromTemplate,
    rewriteLinesByPersona,
    roleOf,
    type PlayLine,
    type PlayPersona,
    type PlayTemplate
} from '../utils/play_video'

/**
 * 影视换脸改词的台词状态：原片基线 diff、增删句、人设一键替换（AI 失败回落本地拼接）、校验。
 */
export function usePlayLines() {
    const lines = ref<PlayLine[]>([])
    const originalLines = ref<string[]>([])
    const appliedPersona = ref<PlayPersona | null>(null)
    const rewriting = ref(false)
    let appliedSnapshot = ''

    const speechSeconds = computed(() => {
        const total = lines.value.reduce((n, l) => n + (l.text || '').trim().length, 0)
        return Math.ceil(total / SPEECH_RUNES_PER_SECOND)
    })
    const speechOver = computed(() => speechSeconds.value > MAX_SPEECH_SECONDS)

    const isRight = (line: PlayLine, i: number) => (line.role ? line.role === 'B' : i % 2 === 1)
    const isChanged = (line: PlayLine, i: number) =>
        i >= originalLines.value.length || (line.text || '').trim() !== originalLines.value[i]
    const changedCount = computed(() => lines.value.filter((l, i) => isChanged(l, i)).length)
    const scriptHint = computed(() => {
        if (appliedPersona.value) {
            return `已按「${appliedPersona.value.persona_name}」替换全部台词，每句仍可单独修改`
        }
        if (!originalLines.value.length) return '点任意一句直接改写'
        return changedCount.value
            ? `已手动改写 ${changedCount.value} 句，不满意可恢复原片台词`
            : '点任意一句直接改写台词；或选人设一键替换'
    })

    const addLine = () => {
        if (lines.value.length >= MAX_LINES) return
        lines.value.push({ role: roleOf(lines.value.length), text: '' })
    }

    const removeLine = (i: number) => {
        if (lines.value.length <= 1) return
        lines.value.splice(i, 1)
    }

    const fillFromTemplate = (tpl: PlayTemplate | null) => {
        originalLines.value = linesFromTemplate(tpl).map((l) => l.text.trim())
        lines.value = linesFromTemplate(tpl)
        if (lines.value.length === 0) addLine()
        appliedPersona.value = null
    }

    const resetScript = (tpl: PlayTemplate | null) => {
        fillFromTemplate(tpl)
        uni.$u.toast('已恢复原片台词')
    }

    /** 清洗并校验；不通过时 toast 并返回 null */
    const validate = (): PlayLine[] | null => {
        const clean = lines.value.map((l) => ({ role: l.role, text: (l.text || '').trim() }))
        if (clean.length === 0 || clean.some((l) => !l.text)) {
            uni.$u.toast('台词不能有空句')
            return null
        }
        if (speechOver.value) {
            uni.$u.toast(
                `台词预估口播约 ${speechSeconds.value} 秒，超过 ${MAX_SPEECH_SECONDS} 秒上限，请精简`
            )
            return null
        }
        return clean
    }

    /** 按人设 AI 改写；AI 失败回落本地拼接规则，保底体验不断 */
    const applyPersona = async (p: PlayPersona, templateName: string) => {
        if (rewriting.value) return false
        const current = validate()
        if (!current) return false
        rewriting.value = true
        uni.showLoading({ title: 'AI 改写中…', mask: true })
        try {
            const data: any = await rewritePlayVideoLines({
                persona_id: p.id,
                lines: current,
                template_name: templateName
            })
            lines.value = (data.lines || []).map((l: any, i: number) => ({
                role: l.role || current[i]?.role || roleOf(i),
                text: l.text || ''
            }))
            uni.$u.toast(`已按「${p.persona_name}」替换 ${lines.value.length} 句台词`)
        } catch (e) {
            const base = originalLines.value.length
                ? originalLines.value
                : current.map((l) => l.text)
            lines.value = rewriteLinesByPersona(base, p).map((text, i) => ({
                role: current[i]?.role || roleOf(i),
                text
            }))
            uni.$u.toast('AI 繁忙，已用快速模式替换')
        } finally {
            uni.hideLoading()
            rewriting.value = false
            appliedPersona.value = p
            appliedSnapshot = lines.value.map((l) => l.text).join('\n')
        }
        return true
    }

    // 手动改动任意一句即撤销「整套按人设替换」的声明
    watch(
        lines,
        () => {
            if (
                appliedPersona.value &&
                lines.value.map((l) => l.text).join('\n') !== appliedSnapshot
            ) {
                appliedPersona.value = null
            }
        },
        { deep: true }
    )

    return {
        lines,
        originalLines,
        appliedPersona,
        rewriting,
        speechSeconds,
        speechOver,
        isRight,
        isChanged,
        scriptHint,
        addLine,
        removeLine,
        fillFromTemplate,
        resetScript,
        validate,
        applyPersona
    }
}
