<template>
    <view
        class="rounded-[32rpx] px-[28rpx] py-[28rpx] mb-[24rpx] border-[2rpx] border-solid border-[#CFE0FF]"
        style="background: linear-gradient(160deg, #eef5ff 0%, #ffffff 62%)"
    >
        <view class="flex items-start">
            <view
                class="w-[54rpx] h-[54rpx] rounded-[18rpx] flex items-center justify-center mr-[16rpx] shrink-0"
                style="background: linear-gradient(135deg, #4d9bff, #2f73f6)"
            >
                <u-icon name="edit-pen" color="#ffffff" size="28"></u-icon>
            </view>
            <view class="flex-1">
                <text class="block text-[30rpx] leading-[1.35] font-black text-[#111827]">
                    一句话，AI 帮你全填好
                </text>
                <text class="block text-[22rpx] leading-[1.5] text-[#7B8798] mt-[4rpx]">
                    打字说清自己的情况，或点「说一段」语音输入，AI 自动补全下面的信息
                </text>
            </view>
        </view>

        <view
            class="mt-[22rpx] rounded-[24rpx] bg-white px-[24rpx] py-[20rpx] border-[2rpx] border-solid border-[#E4EDFB]"
        >
            <textarea
                v-model="draft"
                :maxlength="MAX_LENGTH"
                :disabled="analyzing"
                :placeholder="PLACEHOLDER"
                placeholder-style="color:#BFC9D9;font-size:26rpx;line-height:1.65;"
                class="w-full"
                style="height: 200rpx; font-size: 26rpx; line-height: 1.65; color: #1d2129"
            />
            <view class="flex items-center justify-between mt-[6rpx]">
                <text class="text-[22rpx] text-[#C0C8D8]"
                    >{{ draft.trim().length }} / {{ MAX_LENGTH }}</text
                >
                <text
                    class="text-[22rpx] font-semibold text-primary"
                    @click="showExample = !showExample"
                >
                    {{ showExample ? '收起示例' : '不知道怎么写？看示例' }}
                </text>
            </view>
        </view>

        <view
            v-if="showExample"
            class="mt-[16rpx] px-[24rpx] py-[18rpx] rounded-[24rpx] bg-[#FFFBEB]"
        >
            <text class="block text-[22rpx] leading-[1.7] text-[#92400E]">{{ EXAMPLE_TEXT }}</text>
        </view>

        <view class="flex items-center mt-[24rpx]">
            <view
                class="flex-1 h-[84rpx] rounded-[24rpx] flex items-center justify-center active:opacity-80"
                :class="analyzing ? 'opacity-70' : ''"
                style="
                    background: linear-gradient(135deg, #3d82f7, #2563eb);
                    box-shadow: 0 8rpx 24rpx rgba(47, 115, 246, 0.22);
                "
                @click="handleAnalyze"
            >
                <u-loading v-if="analyzing" mode="circle" size="30" color="#ffffff"></u-loading>
                <text v-else class="text-white text-[28rpx] font-bold">AI 分析并填写</text>
                <text
                    v-if="!analyzing && tokenScore > 0"
                    class="ml-[10rpx] text-[22rpx] text-[#DCE9FF]"
                >
                    消耗 {{ tokenScore }} 算力
                </text>
            </view>
            <view
                class="ml-[16rpx] h-[84rpx] px-[24rpx] rounded-[24rpx] bg-white flex items-center justify-center border-[2rpx] border-solid border-[#DDE7F8]"
                :class="analyzing ? 'opacity-50' : 'active:opacity-80'"
                @click="emit('open-voice')"
            >
                <u-icon name="mic" color="#2F73F6" size="28"></u-icon>
                <text class="ml-[8rpx] text-[24rpx] font-semibold text-[#2F73F6]">说一段</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        /** 输入内容。语音转写由父层写回这里，用户改完再点分析 */
        modelValue?: string
        analyzing?: boolean
        /** 单次分析消耗的算力，0 表示未配置（此时不展示） */
        tokenScore?: number
    }>(),
    {
        modelValue: '',
        analyzing: false,
        tokenScore: 0
    }
)

const emit = defineEmits<{
    (event: 'update:modelValue', value: string): void
    (event: 'analyze', contents: string): void
    (event: 'open-voice'): void
}>()

const MAX_LENGTH = 1000

/** 少于这个字数 AI 基本分析不出有效信息，先拦住避免白扣算力 */
const MIN_LENGTH = 10

const PLACEHOLDER =
    '例：我在杭州开精品咖啡店，主要卖自家烘焙豆子，客人是 22-35 岁上班族，店里能手冲教学，拿过上海咖啡大赛银奖'

const EXAMPLE_TEXT =
    '我们叫「浮光咖啡」，是精品咖啡店。主理人 Lena 当 IP，她是哥伦比亚咖啡品鉴师，风格专业权威 + 亲切邻家。目标客户是 22-35 岁上班族，男女各半。产品特点：自家烘焙豆子、手冲教学、社区活动空间。内容想做知识分享 + 幕后记录，不想做搞笑剧情。拿过上海咖啡大赛银奖，店在杭州西湖区。'

const draft = computed({
    get: () => props.modelValue,
    set: (value: string) => emit('update:modelValue', value)
})
const showExample = ref(false)

// textarea 的 maxlength 只拦键盘输入，语音转写是程序赋值，这里补一道截断
watch(
    () => props.modelValue,
    (value) => {
        if (value.length > MAX_LENGTH) emit('update:modelValue', value.slice(0, MAX_LENGTH))
    }
)

const handleAnalyze = (): void => {
    if (props.analyzing) return
    const contents = draft.value.trim()
    if (!contents) {
        uni.$u.toast('先输入你的情况，AI 才能分析')
        return
    }
    if (contents.length < MIN_LENGTH) {
        uni.$u.toast(`再多写几句（至少 ${MIN_LENGTH} 字），AI 才分析得准`)
        return
    }
    emit('analyze', contents)
}
</script>
