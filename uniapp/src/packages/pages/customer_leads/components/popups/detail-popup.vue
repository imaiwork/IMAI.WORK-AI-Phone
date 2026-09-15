<template>
    <popup-bottom
        v-model="show"
        height="70%"
        custom-class="bg-white"
        :clearable="false"
        @input="emit('update:modelValue', $event)"
    >
        <template #header>
            <popup-header
                :customer="customer"
                title="客户详情"
                @close="emit('update:modelValue', false)"
            />
        </template>
        <template #content>
            <scroll-view scroll-y class="h-full">
                <view class="px-[40rpx] pt-[8rpx] pb-[calc(32rpx+env(safe-area-inset-bottom))]">
                    <action-row
                        v-for="row in customer?.rows || []"
                        :key="row.label"
                        :title="row.label"
                        :desc="row.value"
                        :show-arrow="false"
                    />
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import ActionRow from '../action-row.vue'
import PopupHeader from '../popup-header.vue'
import type { Customer } from '../types'

const props = defineProps<{
    modelValue: boolean
    customer: Customer | null
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', v: boolean): void
}>()

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit('update:modelValue', v)
})
</script>
