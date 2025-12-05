<template>
	<popup width="700px" title="查看回复" ref="popRef" @close="close">
		<Markdown :content="replyContent" />
	</popup>
</template>

<script setup lang="ts">
import popup from "@/components/popup/index.vue";

const emit = defineEmits(["close"]);
const popRef = shallowRef();
const replyContent = ref("");

const open = (reply: any) => {
	popRef.value?.open();
	replyContent.value = reply;
};

const close = () => {
	emit("close");
};

defineExpose({ open, close });
</script>

<style scoped lang="scss"></style>
