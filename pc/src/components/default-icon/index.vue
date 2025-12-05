<template>
	<div
		class="gizmo-shadow-stroke relative flex w-full h-full items-center justify-center rounded-full bg-white">
		<Icon name="local-icon-app" :size="iconSize"></Icon>
	</div>
</template>

<script setup lang="ts">
defineProps({
	iconSize: {
		type: Number,
		default: 20,
	},
});
</script>

<style scoped></style>
