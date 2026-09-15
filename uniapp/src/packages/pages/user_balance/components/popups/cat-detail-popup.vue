<template>
    <popup-bottom
        v-model="show"
        height="82%"
        custom-class="bg-[#F3F5FA]"
        :clearable="false"
        :mask-close-able="true">
        <template #header>
            <view class="bg-white px-[40rpx] pt-[32rpx] pb-[28rpx]">
                <view class="mx-auto mb-[28rpx] h-[8rpx] w-[80rpx] rounded-full bg-[#E5E7EB]" />
                <view class="flex items-center justify-between gap-[20rpx]">
                    <view class="flex min-w-0 items-center gap-[24rpx]">
                        <view
                            class="flex h-[80rpx] w-[80rpx] flex-shrink-0 items-center justify-center rounded-[24rpx]"
                            :style="{ background: meta.bg }">
                            <u-icon :name="meta.icon" :color="meta.color" size="36" />
                        </view>
                        <view class="min-w-0">
                            <text class="block truncate text-[33rpx] font-bold text-[#1A1A1A]">
                                {{ cat?.name || meta.name }}
                            </text>
                            <text class="mt-[4rpx] block text-[23rpx] text-[#9AA3B2]">{{ subtitle }}</text>
                        </view>
                    </view>
                    <view
                        class="flex h-[56rpx] w-[56rpx] flex-shrink-0 items-center justify-center rounded-full bg-[#F3F4F6]"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" color="#888888" size="28" />
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <scroll-view scroll-y class="h-full w-full">
                <view class="px-[32rpx] pt-[28rpx] pb-[calc(48rpx+env(safe-area-inset-bottom))]">
                    <text class="sec-label">本期消耗</text>

                    <record-skeleton v-if="loading" :rows="4" />

                    <view v-else-if="!items.length" class="sub-card flex flex-col items-center py-[72rpx]">
                        <view
                            class="mb-[20rpx] flex h-[96rpx] w-[96rpx] items-center justify-center rounded-full bg-[#F3F6FB]">
                            <u-icon name="hourglass" color="#C3CCDA" size="40" />
                        </view>
                        <text class="text-[25rpx] font-semibold text-[#8A93A3]">
                            这一天没有用到{{ cat?.name || meta.name }}
                        </text>
                        <text class="mt-[8rpx] text-[22rpx] text-[#C3CCDA]">换一天看看，或从「计费标准」查单价</text>
                    </view>

                    <block v-else>
                        <view v-for="item in items" :key="item.change_type" class="sub-card mb-[24rpx]">
                            <view class="item-head">
                                <view class="flex min-w-0 items-center gap-[16rpx]">
                                    <view
                                        class="h-[28rpx] w-[8rpx] flex-shrink-0 rounded-full"
                                        :style="{ background: meta.color }" />
                                    <text class="break-all text-[27rpx] font-bold leading-[1.4] text-[#1A1A1A]">
                                        {{ item.name }}
                                    </text>
                                </view>
                                <text
                                    class="flex-shrink-0 text-[27rpx] font-extrabold"
                                    :style="{ color: meta.color }">
                                    {{ signedConsume(item.total) }}
                                </text>
                            </view>
                            <view
                                v-for="(rec, index) in item.records"
                                :key="rec.id"
                                class="rec-row"
                                :class="{ 'rec-row--border': index < item.records.length - 1 }">
                                <view class="min-w-0 flex-1">
                                    <text class="block break-all text-[25rpx] leading-[1.5] text-[#333333]">
                                        {{ rec.detail }}
                                    </text>
                                    <view v-if="extraEntries(rec.extra).length" class="mt-[12rpx] flex flex-wrap gap-[10rpx]">
                                        <view v-for="entry in extraEntries(rec.extra)" :key="entry.key" class="rec-tag">
                                            <text class="text-[#9CA3AF]">{{ entry.key }}：</text>
                                            <text class="font-medium text-[#4B5563]">{{ entry.value }}</text>
                                        </view>
                                    </view>
                                    <text class="mt-[10rpx] block text-[21rpx] text-[#B3BAC7]">{{ rec.time }}</text>
                                </view>
                                <!-- 扣费橙 / 补退绿，与团队消耗页同一套配色 -->
                                <text
                                    class="flex-shrink-0 pt-[2rpx] text-[26rpx] font-bold"
                                    :class="isRefund(rec) ? 'text-[#16A34A]' : 'text-[#F59E0B]'">
                                    {{ rec.change_amount_desc }}
                                </text>
                            </view>
                        </view>
                    </block>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { AccountLogAction, CAT_META, CAT_META_FALLBACK, type CatMeta } from '../../_enums'
import { extraEntries, formatWithComma, signedConsume } from '../../_utils'
import RecordSkeleton from '../record-skeleton.vue'

interface CatDetail {
    total?: number
    count?: number
    items?: any[]
}

const props = defineProps<{
    modelValue: boolean
    cat: { key: string; name: string } | null
    dateLabel: string
    loading: boolean
    detail: CatDetail | null
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
}>()

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit('update:modelValue', v)
})

const meta = computed<CatMeta>(() => CAT_META[props.cat?.key || ''] || CAT_META_FALLBACK)

const items = computed<any[]>(() => (Array.isArray(props.detail?.items) ? props.detail!.items : []))

const isRefund = (rec: any) => Number(rec?.action) === AccountLogAction.Inc

// 副标题报「几笔」而不是「几个计费项」：这一屏只看扣费明细，别让人以为这一天用了 N 个功能
const subtitle = computed(() => {
    if (props.loading) return `${props.dateLabel}消耗统计中`
    const count = Number(props.detail?.count) || 0
    if (!count) return `${props.dateLabel}没有消耗`
    return `${props.dateLabel}消耗 ${formatWithComma(props.detail?.total)} 算力 · 共 ${count} 笔`
})
</script>

<style lang="scss" scoped>
.sec-label {
    @apply mb-[16rpx] block px-[12rpx] text-[25rpx] font-bold text-[#8A93A3];
}

.sub-card {
    @apply overflow-hidden rounded-[28rpx] bg-white;
    box-shadow: 0 4rpx 20rpx rgba(99, 120, 200, 0.07);
}

.item-head {
    @apply flex items-center justify-between gap-[20rpx] border-[0] border-b-[2rpx] border-solid border-[#F2F4F9] px-[32rpx] pt-[28rpx] pb-[20rpx];
}

.rec-row {
    @apply flex items-start gap-[24rpx] px-[32rpx] py-[24rpx];

    &--border {
        @apply border-[0] border-b-[2rpx] border-solid border-[#F2F4F9];
    }
}

.rec-tag {
    @apply flex max-w-full flex-wrap items-center rounded-[8rpx] bg-[#F5F6FA] px-[14rpx] py-[6rpx] text-[22rpx] leading-[1.5] break-all;
}
</style>
