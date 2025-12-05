<template>
	<div class="layout-default flex flex-col h-screen w-full">
		<div
			class="bg-primary text-white flex items-center justify-center flex-shrink-0 h-[40px]"
			v-if="config.tips && config.tips != 'ok'">
			<div class="font-bold" v-html="config.tips"></div>
		</div>
		<div class="grow min-h-0 flex w-full">
			<div class="app-aside">
				<layout-sidebar />
			</div>

			<div class="flex-1 flex flex-col min-w-0">
				<div class="app-header">
					<layout-header />
				</div>
				<div class="app-main flex-1 min-h-0">
					<layout-main />
				</div>
			</div>
		</div>
	</div>
</template>

<script setup lang="ts">
import LayoutMain from "./components/main.vue";
import LayoutSidebar from "./components/sidebar/index.vue";
import LayoutHeader from "./components/header/index.vue";
import useAppStore from "@/stores/modules/app";

const appStore = useAppStore();
const config = computed(() => appStore.config);
</script>
