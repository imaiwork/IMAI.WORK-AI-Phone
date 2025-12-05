<template>
	<main class="main-wrap h-full bg-page" ref="mainRef">
		<el-scrollbar>
			<div class="p-4">
				<router-view v-if="isRouteShow" v-slot="{ Component, route }">
					<transition name="slide-fade" mode="out-in">
						<keep-alive :include="includeList" :max="20">
							<div :key="route.fullPath">
								<component :is="Component" />
							</div>
						</keep-alive>
					</transition>
				</router-view>
			</div>
		</el-scrollbar>
	</main>
</template>

<script setup lang="ts">
import useAppStore from "@/stores/modules/app";
import useTabsStore from "@/stores/modules/multipleTabs";
import useSettingStore from "@/stores/modules/setting";
import { useElementSize, watchThrottled } from "@vueuse/core";
import { shallowRef, computed } from "vue";

const appStore = useAppStore();
const tabsStore = useTabsStore();
const settingStore = useSettingStore();
const mainRef = shallowRef<HTMLDivElement>();
const isRouteShow = computed(() => appStore.isRouteShow);
const includeList = computed(() =>
	settingStore.openMultipleTabs ? tabsStore.getCacheTabList : []
);
const { height } = useElementSize(mainRef);
watchThrottled(height, (val) => {
	appStore.layoutHeight = val;
});
</script>

<style lang="scss" scoped>
/* 动画期间的过渡效果 */
.slide-fade-enter-active {
	transition: opacity 0.5s ease-out, transform 0.5s ease-out;
	will-change: opacity, transform;
}

.slide-fade-leave-active {
	transition: opacity 0.2s ease-out, transform 0.2s ease-out;
	will-change: opacity, transform;
}

/* 进入动画的初始状态 */
.slide-fade-enter-from {
	opacity: 0;
	transform: translateX(-2%);
}

/* 进入动画的结束状态 */
.slide-fade-enter-to {
	opacity: 1;
	height: 100vh;
	transform: translateX(0%);
}

/* 离开动画的结束状态 */
.slide-fade-leave-from {
	opacity: 1;
	height: 100vh;
	transform: translateX(0%);
}

/* 离开动画的初始状态 */
.slide-fade-leave-to {
	opacity: 0;
	height: 100vh;
	transform: translateX(2%);
}
</style>
