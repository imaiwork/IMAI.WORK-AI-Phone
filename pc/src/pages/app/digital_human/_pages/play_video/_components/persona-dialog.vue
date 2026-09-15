<template>
    <ElDialog
        :model-value="modelValue"
        width="420px"
        align-center
        title="按人设一键替换台词"
        @update:model-value="emit('update:modelValue', $event)"
    >
        <div class="text-[12px] text-[#9CA3AF] -mt-2 mb-3">
            保留名场面原句，把最后一句接上你的行业、人设和转化钩子
        </div>
        <div
            v-if="loading"
            class="py-10 text-center text-[13px] text-[#9CA3AF]"
        >
            加载中…
        </div>
        <div
            v-else-if="personas.length === 0"
            class="py-10 text-center text-[13px] text-[#9CA3AF]"
        >
            还没有人设，先去「IP 人设」创建一个
        </div>
        <div v-else class="max-h-[360px] overflow-y-auto flex flex-col gap-2">
            <div
                v-for="p in personas"
                :key="p.id"
                class="flex items-center gap-3 p-3 rounded-xl border-2"
                :class="[
                    applied?.id === p.id
                        ? 'bg-[#F5F9FF] border-[#2680F7]'
                        : 'bg-[#F7F8FA] border-transparent hover:border-[#C9D2E0]',
                    rewriting
                        ? 'cursor-not-allowed opacity-70'
                        : 'cursor-pointer'
                ]"
                @click="handlePick(p)"
            >
                <div
                    class="w-9 h-9 rounded-xl bg-[#EBF2FF] flex items-center justify-center flex-shrink-0"
                >
                    <Icon name="el-icon-User" :size="16" color="#2680F7" />
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-[13px] font-bold text-[#0D1117] truncate">
                        {{ p.persona_name }}
                    </div>
                    <div class="text-[11px] text-[#9CA3AF] truncate">
                        {{
                            [p.industry, p.main_business || p.quick_desc]
                                .filter(Boolean)
                                .join(' · ') || '未填写行业信息'
                        }}
                    </div>
                </div>
            </div>
        </div>
        <div class="text-[11px] text-[#C0C6D0] mt-3">
            {{
                rewriting
                    ? 'AI 正在按人设改写台词…'
                    : '替换后每句仍可单独修改；不满意点「恢复原片台词」可还原'
            }}
        </div>
    </ElDialog>
</template>

<script setup lang="ts">
import { getPersonList } from '@/api/person'
import type { PlayPersona } from '../_typings'

const props = defineProps<{
    modelValue: boolean
    applied: PlayPersona | null
    rewriting: boolean
}>()
const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'apply', persona: PlayPersona): void
}>()

const loading = ref(false)
const loaded = ref(false)
const personas = ref<PlayPersona[]>([])

const loadPersonas = async () => {
    loading.value = true
    try {
        const res: any = await getPersonList({ page_no: 1, page_size: 50 })
        personas.value = (res?.lists ??
            res?.data?.lists ??
            res ??
            []) as PlayPersona[]
        loaded.value = true
    } catch (e) {
        personas.value = []
    } finally {
        loading.value = false
    }
}

watch(
    () => props.modelValue,
    (open) => {
        if (open && !loaded.value && !loading.value) loadPersonas()
    }
)

const handlePick = (p: PlayPersona) => {
    if (props.rewriting) return
    emit('apply', p)
}
</script>
