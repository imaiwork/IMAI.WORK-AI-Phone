<template>
    <ElContainer class="layout-default h-full flex flex-col min-w-[375px]" :style="[{ height: `${windowHeight}px` }]">
        <ElContainer class="min-h-0 grow">
            <LayoutAside></LayoutAside>
            <ElMain
                class="!p-0 transition-all duration-300 mt-[var(--nav-height)]"
                :class="{ 'ml-[var(--aside-width)]': !hideSidebar }">
                <div class="flex flex-col h-full relative">
                    <ElHeader height="auto" style="padding: 0">
                        <LayoutHeader />
                    </ElHeader>
                    <ElMain class="grow min-h-0 !p-0">
                        <LayoutMain>
                            <template v-if="$slots?.mainLeft" #mainLeft>
                                <slot name="mainLeft" />
                            </template>
                            <slot />
                            <template v-if="$slots?.mainRight" #mainRight>
                                <slot name="mainRight" />
                            </template>
                        </LayoutMain>
                    </ElMain>
                </div>
            </ElMain>
        </ElContainer>
    </ElContainer>
</template>
<script lang="ts" setup>
import { ElContainer, ElAside, ElMain, ElHeader, ElFooter } from "element-plus";
import { useWindowSize } from "@vueuse/core";
import LayoutAside from "./components/aside/index.vue";
import LayoutHeader from "./components/header/index.vue";
import LayoutMain from "./components/main/index.vue";
import { useAppStore } from "@/stores/app";

const appStore = useAppStore();
const hideSidebar = computed(() => appStore.hideSidebar);

const { height: windowHeight, width: windowWidth } = useWindowSize({
    includeScrollbar: false,
});

watchEffect(() => {
    appStore.toggleSidebar(windowWidth.value < 1000);
});
</script>
<style lang="scss" scoped>
.el-aside {
    transition: all 0.3s;
}
</style>
