<template>
    <view class="bg-white rounded-[32rpx] shadow-[0_4rpx_24rpx_rgba(99,120,200,0.07)] p-[32rpx]">
        <view class="flex items-center gap-[28rpx]">
            <image
                v-if="persona.avatar"
                :src="persona.avatar"
                mode="aspectFill"
                class="w-[128rpx] h-[128rpx] rounded-full shrink-0 border-[5rpx] border-solid border-[#EEF3FF]"
            />
            <view
                v-else
                class="w-[128rpx] h-[128rpx] rounded-full shrink-0 bg-[#F4F6FA] flex items-center justify-center"
            >
                <u-icon name="account" color="#C3CAD6" :size="46"></u-icon>
            </view>

            <view class="flex-1 min-w-0">
                <view class="flex items-center gap-[12rpx]">
                    <text class="text-[32rpx] font-bold text-main line-clamp-1">
                        {{ persona.name }}
                    </text>
                    <text
                        class="shrink-0 px-[16rpx] py-[4rpx] rounded-[12rpx] text-[21rpx] font-bold"
                        :class="tagStyle"
                    >
                        {{ persona.tag }}
                    </text>
                </view>

                <view class="flex items-center gap-[12rpx] mt-[14rpx] min-w-0">
                    <view
                        class="w-[14rpx] h-[14rpx] rounded-full shrink-0"
                        :style="{ backgroundColor: status.color }"
                    ></view>
                    <text
                        class="text-[24rpx] font-semibold shrink-0"
                        :style="{ color: status.color }"
                    >
                        {{ status.text }}
                    </text>
                    <template v-if="persona.devices">
                        <text class="text-[24rpx] text-[#DCE0E8] shrink-0">|</text>
                        <text class="text-[24rpx] text-[#8A93A3] line-clamp-1">
                            已关联 {{ persona.devices }}
                        </text>
                    </template>
                </view>
            </view>
        </view>

        <!-- 切换人设放到第二行，第一行才有完整宽度显示人设名和设备信息 -->
        <view
            class="flex items-center gap-[12rpx] mt-[24rpx] pt-[24rpx] border-[0] border-t-[2rpx] border-solid border-[#F3F5F9]"
        >
            <text class="text-[23rpx] text-[#9AA3B2] shrink-0">已运营平台</text>
            <image
                v-for="item in boundPlatforms"
                :key="item.key"
                :src="item.icon"
                mode="aspectFit"
                class="w-[44rpx] h-[44rpx] shrink-0"
            />
            <text v-if="!boundPlatforms.length" class="text-[23rpx] text-[#C3CAD6] line-clamp-1">
                未绑定发布账号
            </text>

            <view
                class="ml-auto shrink-0 flex items-center gap-[6rpx] px-[24rpx] h-[56rpx] rounded-full bg-primary-light-9 active:opacity-70"
                @click="emit('switch')"
            >
                <u-icon name="reload" color="var(--color-primary, #0065FB)" :size="20"></u-icon>
                <text class="text-[24rpx] font-bold text-primary">切换人设</text>
            </view>
        </view>
    </view>
</template>

<script setup lang="ts">
import {
    PERSONA_STATUS,
    PERSONA_TAG_STYLE,
    PersonaStatusEnum,
    PersonaTagEnum,
    PLATFORMS,
    type Persona
} from '../enums'

const props = defineProps<{
    persona: Persona
}>()

const emit = defineEmits<{
    (e: 'switch'): void
}>()

const status = computed(
    () => PERSONA_STATUS[props.persona.status] || PERSONA_STATUS[PersonaStatusEnum.LINKED]
)

const tagStyle = computed(
    () => PERSONA_TAG_STYLE[props.persona.tagClass] || PERSONA_TAG_STYLE[PersonaTagEnum.PERSONAL]
)

const boundPlatforms = computed(() =>
    PLATFORMS.filter((item) => (props.persona.accounts?.[item.key] || []).length)
)
</script>
