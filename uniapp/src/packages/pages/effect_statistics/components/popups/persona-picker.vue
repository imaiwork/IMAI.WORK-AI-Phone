<template>
    <popup-bottom v-model="show" height="70%" :clearable="false" :mask-close-able="true">
        <template #header>
            <view class="px-[40rpx] pt-[36rpx] pb-[20rpx] flex items-center">
                <text class="text-[32rpx] font-bold text-main">切换人设</text>
                <view
                    class="ml-auto w-[56rpx] h-[56rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                    @click="emit('update:modelValue', false)"
                >
                    <u-icon name="close" color="#8B9199" :size="20"></u-icon>
                </view>
            </view>
        </template>

        <template #content>
            <view class="h-full w-full flex flex-col">
                <text
                    class="block px-[40rpx] pb-[24rpx] text-[24rpx] text-[#9AA3B2] leading-relaxed"
                >
                    与「IP人设」里的人设一致；每个人设绑定自己的发布账号，数据分开统计
                </text>

                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view
                            class="px-[40rpx] pb-[calc(40rpx+env(safe-area-inset-bottom))] flex flex-col gap-[20rpx]"
                        >
                            <view
                                v-for="item in personas"
                                :key="item.id"
                                class="flex items-center gap-[22rpx] p-[24rpx] rounded-[28rpx] border-[3rpx] border-solid"
                                :class="
                                    item.id === activeId
                                        ? 'border-primary bg-primary-light-9'
                                        : 'border-[#EEF1F6] bg-white'
                                "
                                @click="handlePick(item.id)"
                            >
                                <image
                                    v-if="item.avatar"
                                    :src="item.avatar"
                                    mode="aspectFill"
                                    class="w-[80rpx] h-[80rpx] rounded-full shrink-0"
                                />
                                <view
                                    v-else
                                    class="w-[80rpx] h-[80rpx] rounded-full shrink-0 bg-[#F4F6FA] flex items-center justify-center"
                                >
                                    <u-icon name="account" color="#C3CAD6" :size="32"></u-icon>
                                </view>

                                <view class="flex-1 min-w-0">
                                    <view class="flex items-center gap-[12rpx]">
                                        <text class="text-[27rpx] font-bold text-main line-clamp-1">
                                            {{ item.name }}
                                        </text>
                                        <text
                                            class="shrink-0 px-[14rpx] py-[2rpx] rounded-[10rpx] text-[20rpx] font-bold"
                                            :class="tagStyleOf(item)"
                                        >
                                            {{ item.tag }}
                                        </text>
                                    </view>
                                    <view class="flex items-center mt-[6rpx]">
                                        <text
                                            class="text-[22rpx]"
                                            :style="{ color: statusOf(item).color }"
                                        >
                                            {{ statusOf(item).text }}
                                        </text>
                                        <text class="text-[22rpx] text-[#9AA3B2] line-clamp-1">
                                            · {{ boundTextOf(item) }}
                                        </text>
                                    </view>
                                </view>

                                <u-icon
                                    v-if="item.id === activeId"
                                    name="checkmark"
                                    color="var(--color-primary, #0065FB)"
                                    :size="22"
                                ></u-icon>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import {
    PERSONA_STATUS,
    PERSONA_TAG_STYLE,
    PersonaStatusEnum,
    PersonaTagEnum,
    PLATFORMS,
    type Persona
} from '../../enums'

const props = defineProps<{
    modelValue: boolean
    personas: Persona[]
    activeId: number
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'change', id: number): void
}>()

const show = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const statusOf = (persona: Persona) =>
    PERSONA_STATUS[persona.status] || PERSONA_STATUS[PersonaStatusEnum.LINKED]

const tagStyleOf = (persona: Persona) =>
    PERSONA_TAG_STYLE[persona.tagClass] || PERSONA_TAG_STYLE[PersonaTagEnum.PERSONAL]

const boundTextOf = (persona: Persona) => {
    const bound = PLATFORMS.filter((item) => (persona.accounts?.[item.key] || []).length)
    if (!bound.length) return '未绑定发布账号'
    return `已绑定 ${bound.map((item) => item.short).join('/')}`
}

const handlePick = (id: number) => {
    if (id !== props.activeId) emit('change', id)
    emit('update:modelValue', false)
}
</script>
