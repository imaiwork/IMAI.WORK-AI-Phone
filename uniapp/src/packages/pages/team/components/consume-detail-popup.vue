<template>
    <popup-bottom
        :model-value="modelValue"
        height="82%"
        custom-class="bg-white"
        :clearable="false"
        :mask-close-able="true"
        @update:model-value="emit('update:modelValue', $event)">
        <template #header>
            <view class="px-[40rpx]">
                <view class="flex justify-center">
                    <view class="p-1" @click="emit('update:modelValue', false)">
                        <view class="mt-4 h-[8rpx] w-[72rpx] rounded bg-[#E5EAF3]" />
                    </view>
                </view>
                <view
                    class="mt-[24rpx] mb-[8rpx] flex items-center gap-[24rpx] border-0 border-b border-solid border-[#F0F4FB] pb-[28rpx]">
                    <view
                        class="flex h-[84rpx] w-[84rpx] flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#F1F5F9] text-[30rpx] font-bold text-[#64748B]">
                        <image
                            v-if="member?.avatar"
                            :src="member.avatar"
                            class="h-full w-full"
                            mode="aspectFill" />
                        <text v-else>{{ avatarText }}</text>
                    </view>
                    <view class="min-w-0 flex-1">
                        <text class="mb-[4rpx] block text-[32rpx] font-bold text-[#0F172A]">
                            {{ memberName }} · 算力明细
                        </text>
                        <text class="block text-[24rpx] text-[#94A3B8]">
                            {{ mobileText }} · 当前剩余算力 {{ tokensText }}
                        </text>
                    </view>
                    <view
                        class="flex h-[56rpx] w-[56rpx] flex-shrink-0 items-center justify-center rounded-full bg-[#F1F5F9]"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" size="28" color="#64748B" />
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <scroll-view scroll-y class="h-full" @scrolltolower="loadMore">
                <view v-if="!lists.length && !loading" class="py-[80rpx] text-center text-[24rpx] text-[#94A3B8]">
                    暂无算力明细
                </view>
                <view
                    v-for="item in lists"
                    :key="item.id"
                    class="dt-row px-[40rpx] py-[26rpx]">
                    <view class="mb-[8rpx] flex items-center justify-between gap-[20rpx]">
                        <view class="flex min-w-0 items-center gap-[14rpx]">
                            <text class="truncate text-[26rpx] font-semibold text-[#0F172A]">
                                {{ item.change_type_desc || '算力变动' }}
                            </text>
                            <view
                                class="io-tag"
                                :class="isDec(item) ? 'io-tag--dec' : 'io-tag--inc'">
                                {{ isDec(item) ? '消耗' : '获得' }}
                            </view>
                        </view>
                        <text
                            class="flex-shrink-0 text-[28rpx] font-bold"
                            :class="isDec(item) ? 'text-[#F59E0B]' : 'text-[#16A34A]'">
                            {{ isDec(item) ? '-' : '+' }}{{ formatNum(item.change_amount) }}
                        </text>
                    </view>
                    <view class="flex items-center justify-between gap-[20rpx]">
                        <text class="min-w-0 flex-1 truncate text-[22rpx] text-[#94A3B8]">
                            {{ remarkText(item) }} · {{ item.create_time || '—' }}
                        </text>
                        <text class="flex-shrink-0 text-[22rpx] text-[#64748B]">
                            剩余
                            <text class="font-bold text-[#0F172A]">{{ formatNum(item.left_tokens) }}</text>
                        </text>
                    </view>
                </view>
                <view class="px-[40rpx] pb-[calc(24rpx+env(safe-area-inset-bottom))] pt-[8rpx] text-center">
                    <text class="text-[22rpx] text-[#BBBBBB]">{{ pageHint }}</text>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { getMemberConsumption } from '@/api/team'

const props = defineProps<{
    modelValue: boolean
    member: any | null
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
}>()

const lists = ref<any[]>([])
const loading = ref(false)
const pageNo = ref(1)
const pageSize = 15
const total = ref(0)
const finished = ref(false)

const memberName = computed(() => props.member?.nickname || props.member?.user_name || '成员')
const avatarText = computed(() => String(memberName.value || '?').slice(0, 1))
const tokensText = computed(() => formatNum(props.member?.tokens))
const mobileText = computed(() => maskMobile(String(props.member?.mobile || '')))
const pageHint = computed(() => {
    if (!total.value) return ''
    return `共 ${total.value} 条 · ${pageSize} 条/页 · 第 ${pageNo.value} 页`
})

const formatNum = (n: any) => {
    const v = Number(n)
    if (!Number.isFinite(v)) return '0'
    if (Number.isInteger(v)) return String(v)
    return String(Math.round(v * 100) / 100)
}

const maskMobile = (m: string) => {
    if (!m) return '—'
    if (m.length < 7) return m
    return `${m.slice(0, 3)}****${m.slice(-4)}`
}

const isDec = (item: any) => Number(item?.action) === 2

const remarkText = (item: any) => {
    const r = String(item?.remark || '').trim()
    return r || item?.action_desc || '—'
}

const errText = (e: any) => (typeof e === 'string' ? e : e?.msg || e?.message || '加载失败')

const fetchPage = async (reset = false) => {
    const uid = Number(props.member?.id || props.member?.user_id || 0)
    if (!uid || loading.value) return
    if (!reset && finished.value) return
    loading.value = true
    try {
        const nextPage = reset ? 1 : pageNo.value
        const data: any = await getMemberConsumption({
            user_id: uid,
            page_no: nextPage,
            page_size: pageSize
        })
        const rows = data?.lists || []
        total.value = Number(data?.count) || 0
        lists.value = reset ? rows : lists.value.concat(rows)
        pageNo.value = nextPage
        finished.value = lists.value.length >= total.value || rows.length < pageSize
    } catch (e: any) {
        uni.$u.toast(errText(e))
        if (reset) lists.value = []
    } finally {
        loading.value = false
    }
}

const loadMore = () => {
    if (finished.value || loading.value) return
    pageNo.value += 1
    fetchPage(false)
}

watch(
    () => props.modelValue,
    (v) => {
        if (v) {
            lists.value = []
            finished.value = false
            pageNo.value = 1
            total.value = 0
            fetchPage(true)
        }
    }
)
</script>

<style lang="scss" scoped>
.dt-row:not(:last-child) {
    @apply border-0 border-b-[2rpx] border-solid border-[#F0F4FB];
}

.io-tag {
    @apply flex-shrink-0 rounded-[12rpx] px-[14rpx] py-[2rpx] text-[20rpx] font-bold;

    &--dec {
        @apply bg-[#FFF1F0] text-[#EF4444];
    }

    &--inc {
        @apply bg-[#F0FDF4] text-[#16A34A];
    }
}
</style>
