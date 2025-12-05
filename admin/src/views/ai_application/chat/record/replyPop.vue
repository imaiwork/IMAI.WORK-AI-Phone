<template>
	<popup width="700px" title="查看回复" ref="popRef">
		<div class="flex flex-col gap-4">
			<div>
				<div class="text-gray-500">用户提问内容：</div>
				<div class="text-black border p-2 rounded-md mt-2">
					{{ message }}
				</div>
			</div>
			<div>
				<div class="text-gray-500">回复内容：</div>
				<div class="mt-2">
					<Markdown :content="replyContent" />
				</div>
			</div>
		</div>
	</popup>
</template>

<script setup lang="ts">
import popup from "@/components/popup/index.vue";

const popRef = shallowRef();
const replyContent = ref("");
const message = ref("");

const open = (row: any) => {
	popRef.value?.open();
	replyContent.value = row.reply;
	message.value = row.message;
};

defineExpose({ open });
</script>

<style scoped lang="scss"></style>
