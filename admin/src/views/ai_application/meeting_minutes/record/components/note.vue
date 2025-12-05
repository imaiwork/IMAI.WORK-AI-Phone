<template>
	<div class="h-full">
		<div v-html="content" v-if="content"></div>
		<el-empty v-else />
	</div>
</template>

<script setup lang="ts">
const props = defineProps<{
	content: string;
}>();
</script>
