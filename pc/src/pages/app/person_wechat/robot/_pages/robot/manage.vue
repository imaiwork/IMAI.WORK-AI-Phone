<template>
    <div class="h-full flex flex-col">
        <div class="flex items-center justify-between bg-white p-4 rounded-xl">
            <div>
                <ElBreadcrumb :separator-icon="ArrowRight">
                    <ElBreadcrumbItem>
                        <span class="cursor-pointer text-[#8A8C99] hover:text-primary" @click="close">
                            知识库管理
                        </span>
                    </ElBreadcrumbItem>
                    <ElBreadcrumbItem>
                        <span> 机器人管理 </span>
                    </ElBreadcrumbItem>
                </ElBreadcrumb>
            </div>
            <div class="flex items-center gap-2">
                <ElButton type="primary" @click="handleAdjust">AI角色调整</ElButton>
            </div>
        </div>
        <div class="grow min-h-0 flex flex-col bg-white rounded-xl pt-4 mt-4">
            <div class="px-4">
                <ElTabs v-model="tabName">
                    <ElTabPane label="问答话术" name="1" v-if="false"> </ElTabPane>
                    <ElTabPane label="关键词回复" name="2"> </ElTabPane>
                </ElTabs>
            </div>
            <div class="grow min-h-0">
                <KeywordsPage v-if="tabName === '2'" />
            </div>
        </div>
    </div>
    <edit-pop v-if="showEdit" ref="editPopRef" @close="showEdit = false" />
</template>

<script setup lang="ts">
import { ArrowRight } from "@element-plus/icons-vue";
import KeywordsPage from "./keywords.vue";
import EditPop from "./edit.vue";

const route = useRoute();

const emit = defineEmits<{
    (e: "close"): void;
}>();

const tabName = ref("2");

const showEdit = ref<boolean>(false);
const editPopRef = shallowRef<InstanceType<typeof EditPop>>();
const handleAdjust = async () => {
    showEdit.value = true;
    await nextTick();
    editPopRef.value.open("edit");
    editPopRef.value.getDetail(Number(route.query.id));
};

const close = () => {
    emit("close");
};
</script>

<style scoped></style>
