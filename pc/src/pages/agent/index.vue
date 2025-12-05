<template>
    <div class="h-full">
        <!-- 动态渲染列表页或编辑页 -->
        <component :is="isEdit ? Edit : Lists" @back="back" />
    </div>
</template>

<script setup lang="ts">
import Lists from "./_pages/lists.vue";
import Edit from "./_pages/edit.vue";

/**
 * @description 智能体模块的主入口组件
 * @summary 根据路由查询参数动态地在列表页和编辑页之间切换。
 */

const router = useRouter();
const route = useRoute();

// 计算属性，判断当前是否处于编辑模式
// 如果路由的query中包含id或type，则认为是编辑模式
const isEdit = computed(() => {
    return !!route.query.id || !!route.query.type;
});

/**
 * @description 从编辑页返回到列表页
 * @summary 通过修改路由query来触发视图切换
 */
const back = () => {
    router.replace({ query: {} });
};
</script>

<style scoped></style>
