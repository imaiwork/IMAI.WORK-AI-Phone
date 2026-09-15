<template>
    <popup-bottom v-model="show" height="70%" :clearable="true">
        <template #header>
            <view class="px-[36rpx] pt-[36rpx] pb-[24rpx]">
                <text class="text-[30rpx] font-bold text-[#0D1117] block">按人设一键替换台词</text>
                <text class="text-[21rpx] text-[#9CA3AF] block mt-[8rpx]"
                    >保留名场面原句，把最后一句接上你的行业、人设和转化钩子</text
                >
            </view>
        </template>
        <template #content>
            <view class="h-full w-full flex flex-col">
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-[36rpx]">
                            <view v-if="loading" class="py-[48rpx] text-center">
                                <u-loading mode="circle" size="44" />
                            </view>
                            <view v-else-if="personas.length === 0" class="py-[48rpx] text-center">
                                <text class="text-[24rpx] text-[#9CA3AF]"
                                    >还没有人设，先去「IP 人设」创建一个</text
                                >
                            </view>
                            <view
                                v-for="p in personas"
                                v-else
                                :key="p.id"
                                class="flex items-center gap-[20rpx] p-[24rpx] rounded-[20rpx] mb-[16rpx] border-[3rpx]"
                                :class="[
                                    applied?.id === p.id
                                        ? 'bg-[#F5F9FF] border-[#2680F7]'
                                        : 'bg-[#F7F8FA] border-[transparent]',
                                    rewriting ? 'opacity-60' : 'active:opacity-80'
                                ]"
                                @click="handlePick(p)"
                            >
                                <view
                                    class="w-[64rpx] h-[64rpx] rounded-[18rpx] bg-[#EBF2FF] flex items-center justify-center flex-shrink-0"
                                >
                                    <u-icon name="account" color="#2680F7" size="30" />
                                </view>
                                <view class="flex-1 min-w-0">
                                    <text
                                        class="text-[25rpx] font-bold text-[#0D1117] block line-clamp-1"
                                        >{{ p.persona_name }}</text
                                    >
                                    <text
                                        class="text-[20rpx] text-[#9CA3AF] block mt-[4rpx] line-clamp-1"
                                    >
                                        {{
                                            [p.industry, p.main_business || p.quick_desc]
                                                .filter(Boolean)
                                                .join(' · ') || '未填写行业信息'
                                        }}
                                    </text>
                                </view>
                                <u-icon name="arrow-right" color="#C0C6D0" size="24" />
                            </view>
                        </view>
                    </scroll-view>
                </view>
                <view class="px-[36rpx] pt-[16rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))]">
                    <text class="text-[19rpx] text-[#C0C6D0]">
                        {{
                            rewriting
                                ? 'AI 正在按人设改写台词…'
                                : '替换后每句仍可单独修改；不满意点「恢复原片台词」可还原'
                        }}
                    </text>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getPersonList } from '@/api/person'
import type { PlayPersona } from '../../../utils/play_video'

const props = defineProps<{
    modelValue: boolean
    applied: PlayPersona | null
    rewriting: boolean
}>()
const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'apply', persona: PlayPersona): void
}>()

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit('update:modelValue', v)
})

const loading = ref(false)
const loaded = ref(false)
const personas = ref<PlayPersona[]>([])

const loadPersonas = async () => {
    loading.value = true
    try {
        const data: any = await getPersonList({ page_no: 1, page_size: 50 })
        personas.value = (data?.lists ?? data ?? []) as PlayPersona[]
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
