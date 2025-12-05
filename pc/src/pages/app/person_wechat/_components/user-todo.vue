<template>
    <div class="flex flex-col gap-4">
        <div
            v-for="(item, index) in list"
            :key="index"
            class="border border-solid border-primary-light-9 rounded-xl p-4 min-h-[100px] relative pt-10">
            <div class="absolute top-0 left-0 bg-primary rounded-tl-xl rounded-br-xl">
                <div class="flex items-center gap-2 px-3 py-1">
                    <Icon name="local-icon-send_plane_fill" color="#fff"></Icon>
                    <span class="text-white text-xs">{{ item.todo_time }}</span>
                </div>
            </div>
            <div class="absolute right-2 top-2 z-20">
                <ElButton type="danger" size="small" @click="emit('delete', item.id)">
                    <Icon name="el-icon-Delete" color="#fff"></Icon>
                    <span class="text-white text-xs">删除</span>
                </ElButton>
                <ElButton type="primary" size="small" @click="emit('edit', item)">
                    <Icon name="el-icon-Edit" color="#fff"></Icon>
                    <span class="text-white text-xs">编辑</span>
                </ElButton>
                <ElButton type="primary" size="small" @click="handleFoldContent(item.id)">
                    <Icon name="el-icon-Fold" color="#fff"></Icon>
                    <span class="text-white text-xs">折叠/展开</span>
                </ElButton>
            </div>
            <div class="absolute right-2 bottom-2 text-[#474747]">
                {{ item.todo_type == 0 ? "添加代办" : "自动跟进" }}
            </div>
            <div
                class="h-full flex flex-col gap-2 overflow-hidden transition-all duration-500 ease-in-out transform origin-top text-[#8A8C99]"
                :class="[
                    foldIndex.includes(item.id)
                        ? 'opacity-0 max-h-0 scale-y-95'
                        : 'opacity-100 max-h-[2000px] scale-y-100',
                ]">
                {{ item.todo_content }}
            </div>
            <div class="h-full flex items-center justify-center mt-4 text-gray-500" v-if="foldIndex.includes(item.id)">
                内容被折叠
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
const props = defineProps<{
    list: any[];
}>();

const emit = defineEmits<{
    (e: "edit", id: string | number): void;
    (e: "delete", id: string | number): void;
}>();

const foldIndex = ref<string[]>([]);

const handleFoldContent = (id: any) => {
    if (foldIndex.value.includes(id)) {
        foldIndex.value = foldIndex.value.filter((item) => item !== id);
    } else {
        foldIndex.value.push(id);
    }
};
</script>

<style scoped>
.content-wrapper {
    @apply h-full bg-primary-light-9 flex p-2 rounded-lg;
}
</style>
