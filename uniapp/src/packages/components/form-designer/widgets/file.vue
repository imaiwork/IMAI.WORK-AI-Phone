<template>
	<view class="widget-file">
		<file-upload v-model="value" :limit="1" fileType="image" is-gpt>
			<template #trigger>
				<view
					class="h-[268rpx] w-full bg-[#F7FBFF] rounded-lg flex flex-col items-center justify-center">
					<image
						src="@/packages/static/images/common/upload.png"
						class="w-[58rpx] h-[58rpx]"></image>
					<view class="text-xs text-[#B4B4B4] mt-2">
						点击此处上传图片
					</view>
				</view>
			</template>
		</file-upload>
	</view>
</template>

<script setup lang="ts">
const props = withDefaults(
	defineProps<{
		modelValue: any[];
	}>(),
	{
		modelValue: () => [],
	}
);
const emit = defineEmits<{
	(event: "update:modelValue", value: any): void;
}>();
const value = computed({
	get() {
		return props.modelValue || [];
	},
	set(value) {
		emit("update:modelValue", value);
	},
});
</script>

<style scoped></style>
