<template>
	<view class="widget-textarea">
		<u-input
			class="w-full"
			v-model="value"
			:placeholder="placeholder"
			:auto-height="autosize"
			:custom-style="styles"
			placeholder-style="color: #6a6a6a;font-size: 24rpx;"
			:maxlength="$attrs.maxlength"
			type="textarea">
		</u-input>
		<view
			v-if="showWordLimit"
			class="text-xs bg-page text-muted absolute right-[20rpx] bottom-[10rpx] leading-[28rpx]">
			{{ value?.length }} / {{ $attrs.maxlength }}
		</view>
	</view>
</template>

<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(
	defineProps<{
		modelValue: string;
		placeholder?: string;
		rows?: number;
		showWordLimit?: boolean;
		autosize: boolean;
	}>(),
	{
		modelValue: "",
		placeholder: "请输入",
		showWordLimit: true,
		autosize: false,
		rows: 1,
	}
);
const emit = defineEmits<{
	(event: "update:modelValue", value: any): void;
}>();
const value = computed({
	get() {
		return props.modelValue;
	},
	set(value) {
		emit("update:modelValue", value);
	},
});

const styles = computed(() => {
	return {
		height: props.autosize ? "auto" : `${props.rows * 40}rpx`,
		minHeight: "80rpx",
		fontSize: "28rpx",
	};
});
</script>
<style lang="scss" scoped>
.widget-textarea {
	border-radius: 10rpx;
	padding: 10rpx 20rpx;
	flex: 1;
	display: flex;
	align-items: center;
	position: relative;
	@apply bg-white border border-solid border-[#E8E8E8] rounded-lg;
	.u-input {
		:deep(.u-input__input) {
			@apply text-xs;
		}
	}
}
</style>
