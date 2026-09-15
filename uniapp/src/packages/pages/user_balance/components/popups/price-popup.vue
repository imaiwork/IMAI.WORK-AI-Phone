<template>
    <popup-bottom
        v-model="show"
        height="88%"
        custom-class="bg-[#F3F5FA]"
        :is-disabled-touch="true"
        :clearable="false"
        :mask-close-able="true">
        <template #header>
            <view class="bg-white px-[40rpx] pt-[32rpx] pb-[24rpx]">
                <view class="mx-auto mb-[28rpx] h-[8rpx] w-[80rpx] rounded-full bg-[#E5E7EB]" />
                <view class="flex items-center justify-between gap-[20rpx]">
                    <view class="flex min-w-0 items-center gap-[24rpx]">
                        <view
                            class="flex h-[80rpx] w-[80rpx] flex-shrink-0 items-center justify-center rounded-[24rpx] bg-[#EBF2FF]">
                            <u-icon name="order" color="#2B6EFF" size="36" />
                        </view>
                        <view class="min-w-0">
                            <text class="block text-[33rpx] font-bold text-[#1A1A1A]">计费标准</text>
                            <text class="mt-[4rpx] block text-[23rpx] text-[#9AA3B2]">
                                {{ showSkeleton ? '价目表加载中' : `共 ${list.length} 个计费项 · 按功能类型分组` }}
                            </text>
                        </view>
                    </view>
                    <view
                        class="flex h-[56rpx] w-[56rpx] flex-shrink-0 items-center justify-center rounded-full bg-[#F3F4F6]"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" color="#888888" size="28" />
                    </view>
                </view>

                <!-- 几十项一屏翻不完，搜索比目录更快 -->
                <view class="search-box mt-[28rpx]">
                    <u-icon name="search" color="#B3BAC7" size="30" />
                    <input
                        v-model="keyword"
                        class="ml-[12rpx] min-w-0 flex-1 text-[26rpx] text-[#1A1A1A]"
                        placeholder="搜功能名，如「混剪」「音色」"
                        placeholder-style="color: #B3BAC7"
                        confirm-type="search" />
                    <view
                        v-if="keyword"
                        class="flex h-[40rpx] w-[40rpx] items-center justify-center"
                        @click="keyword = ''">
                        <u-icon name="close-circle-fill" color="#C0C8D8" size="28" />
                    </view>
                </view>

                <view v-if="!keyword" class="mt-[20rpx] whitespace-nowrap">
                    <scroll-view scroll-x class="w-full" :show-scrollbar="false">
                        <view class="inline-flex gap-[12rpx] pb-[4rpx]">
                            <view
                                class="pchip"
                                :class="{ 'pchip--on': activeGroup === GROUP_ALL }"
                                @click="activeGroup = GROUP_ALL">
                                全部
                            </view>
                            <view
                                v-for="g in groups"
                                :key="g.key"
                                class="pchip"
                                :class="{ 'pchip--on': activeGroup === g.key }"
                                @click="activeGroup = g.key">
                                {{ g.name }}
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>
        </template>
        <template #content>
            <scroll-view scroll-y class="h-full w-full">
                <view class="px-[32rpx] pt-[24rpx] pb-[calc(48rpx+env(safe-area-inset-bottom))]">
                    <!-- 搜索时跨分组平铺：用户搜「混剪」不该先猜它归在哪一类 -->
                    <block v-if="keyword">
                        <view v-if="!searchHits.length" class="sub-card flex flex-col items-center py-[80rpx]">
                            <text class="text-[26rpx] font-semibold text-[#8A93A3]">
                                没找到「{{ keyword }}」相关的计费项
                            </text>
                            <text class="mt-[8rpx] text-[23rpx] text-[#C3CCDA]">换个关键词，或按类型翻</text>
                        </view>
                        <block v-else>
                            <text class="sec-label">匹配 {{ searchHits.length }} 项</text>
                            <view class="sub-card">
                                <view
                                    v-for="(hit, index) in searchHits"
                                    :key="hit.item.scene || hit.item.id"
                                    class="pr-row"
                                    :class="{ 'pr-row--border': index > 0 }">
                                    <view class="min-w-0 flex-1">
                                        <view class="flex flex-wrap items-center gap-[8rpx]">
                                            <text class="cat-pill">{{ hit.groupName }}</text>
                                            <text class="pr-name">{{ hit.item.name }}</text>
                                        </view>
                                        <text v-if="hit.item.description" class="pr-note">
                                            {{ hit.item.description }}
                                        </text>
                                    </view>
                                    <text class="pr-price">{{ priceText(hit.item) }}</text>
                                </view>
                            </view>
                        </block>
                    </block>

                    <record-skeleton v-else-if="showSkeleton" :rows="8" />

                    <block v-else>
                        <view v-if="!visibleGroups.length" class="sub-card py-[80rpx] text-center">
                            <text class="text-[26rpx] font-semibold text-[#8A93A3]">暂无计费项</text>
                        </view>
                        <view v-for="g in visibleGroups" :key="g.key" class="mb-[28rpx]">
                            <view class="mb-[16rpx] flex items-center gap-[16rpx] px-[12rpx]">
                                <text class="text-[27rpx] font-bold text-[#1A1A1A]">{{ g.name }}</text>
                                <text class="n-pill">{{ g.items.length }} 项</text>
                            </view>
                            <view class="sub-card">
                                <view
                                    v-for="(item, index) in g.items"
                                    :key="item.scene || item.id"
                                    class="pr-row"
                                    :class="{ 'pr-row--border': index > 0 }">
                                    <view class="min-w-0 flex-1">
                                        <text class="pr-name">{{ item.name }}</text>
                                        <text v-if="item.description" class="pr-note">{{ item.description }}</text>
                                    </view>
                                    <text class="pr-price">{{ priceText(item) }}</text>
                                </view>
                            </view>
                        </view>
                    </block>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import { buildPriceGroups, priceText } from '../../_utils'
import RecordSkeleton from '../record-skeleton.vue'

const GROUP_ALL = 'all'

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        list: any[]
        loading?: boolean
    }>(),
    { loading: false }
)

const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
}>()

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit('update:modelValue', v)
})

const keyword = ref('')
const activeGroup = ref(GROUP_ALL)

const groups = computed(() => buildPriceGroups(props.list))

// 价目表通常在进页面前就已在 store 里；只有第一次拉且还没数据时才需要骨架屏
const showSkeleton = computed(() => props.loading && !props.list.length)

const visibleGroups = computed(() =>
    activeGroup.value === GROUP_ALL ? groups.value : groups.value.filter((g) => g.key === activeGroup.value)
)

const searchHits = computed(() => {
    const q = keyword.value.trim().toLowerCase()
    if (!q) return []
    const hits: { groupName: string; item: any }[] = []
    groups.value.forEach((g) => {
        g.items.forEach((item) => {
            const text = `${item.name || ''} ${item.description || ''}`.toLowerCase()
            if (text.includes(q)) hits.push({ groupName: g.name, item })
        })
    })
    return hits
})

// 关掉再打开回到「全部」：停在上次筛的那一类，用户会以为价目表统共就这么几项
watch(
    () => props.modelValue,
    (v) => {
        if (!v) {
            keyword.value = ''
            activeGroup.value = GROUP_ALL
        }
    }
)
</script>

<style lang="scss" scoped>
.search-box {
    @apply flex items-center rounded-[20rpx] bg-[#F3F6FB] px-[24rpx] py-[16rpx];
}

.pchip {
    @apply flex-shrink-0 rounded-full bg-[#F3F6FB] px-[24rpx] py-[10rpx] text-[24rpx] font-bold text-[#8A93A3];

    &--on {
        @apply bg-[#2B6EFF] text-white;
    }
}

.sec-label {
    @apply mb-[16rpx] block px-[12rpx] text-[25rpx] font-bold text-[#8A93A3];
}

.sub-card {
    @apply overflow-hidden rounded-[28rpx] bg-white;
    box-shadow: 0 4rpx 20rpx rgba(99, 120, 200, 0.07);
}

.pr-row {
    @apply flex items-start gap-[20rpx] px-[28rpx] py-[22rpx];

    &--border {
        @apply border-[0] border-t-[2rpx] border-solid border-[#F5F7FB];
    }
}

.pr-name {
    @apply text-[26rpx] font-semibold leading-[1.4] text-[#1A1A1A] break-all;
}

.pr-note {
    @apply mt-[6rpx] block text-[21rpx] leading-[1.55] text-[#9AA3B2] break-all;
}

.pr-price {
    @apply flex-shrink-0 whitespace-nowrap pt-[2rpx] text-[25rpx] font-extrabold text-[#1A1A1A];
}

.cat-pill {
    @apply rounded-[8rpx] bg-[#EBF2FF] px-[10rpx] py-[2rpx] text-[19rpx] font-bold text-[#2B6EFF];
}

.n-pill {
    @apply rounded-[10rpx] bg-[#F1F4F9] px-[10rpx] py-[3rpx] text-[20rpx] font-bold text-[#8A93A3];
}
</style>
