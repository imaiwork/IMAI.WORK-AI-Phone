<template>
    <el-container class="layout-default h-full !min-w-[1200px]" :style="[{ height: `${windowHeight}px` }]">
        <el-header height="auto" style="padding: 0">
            <LayoutHeader />
        </el-header>
        <el-container class="min-h-0">
            <el-main class="!p-0">
                <div class="flex flex-col h-full relative">
                    <el-main class="flex-1 !p-0">
                        <LayoutMain>
                            <template v-if="$slots?.mainLeft" #mainLeft>
                                <slot name="mainLeft" />
                            </template>
                            <slot />
                            <template v-if="$slots?.mainRight" #mainRight>
                                <slot name="mainRight" />
                            </template>
                        </LayoutMain>
                    </el-main>
                </div>
            </el-main>
        </el-container>
    </el-container>
</template>
<script lang="ts" setup>
import { ElContainer, ElMain, ElHeader } from "element-plus";
import { useWindowSize } from "@vueuse/core";
import LayoutHeader from "./components/wechat/header/index.vue";
import LayoutMain from "./components/main/index.vue";

const { height: windowHeight } = useWindowSize({
    includeScrollbar: false,
});
</script>
<style lang="scss" scoped>
.el-aside {
    transition: all 0.3s;
}
</style>
