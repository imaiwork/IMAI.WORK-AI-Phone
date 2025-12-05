<template>
    <div class="h-full flex flex-col">
        <div class="px-3">
            <ElInput
                v-model="url"
                :placeholder="`请输入要解析的网页链接，添加多个请按回车键分隔`"
                type="textarea"
                resize="none"
                :rows="6" />
            <div class="flex justify-end my-2">
                <ElButton type="primary" class="!rounded-full" :loading="isLock" @click="lockFn"> 解析 </ElButton>
            </div>
        </div>
        <div class="grow min-h-0">
            <ElScrollbar>
                <div class="px-3 flex flex-col gap-y-4">
                    <div v-for="item in formData" class="mb-4">
                        <data-item
                            v-for="(value, index) in item.data"
                            v-model:data="value.q"
                            :name="item.name"
                            :key="index"
                            :index="index"
                            @delete="handleDeleteStage(index)" />
                    </div>
                </div>
            </ElScrollbar>
        </div>
    </div>
</template>

<script setup lang="ts">
import { webHtmlCapture } from "@/api/knowledge_base";
import type { IDataItem } from "./hook";
import DataItem from "./data-item.vue";

const formData = defineModel<IDataItem[]>("modelValue", { required: true });

const url = ref("");

const { lockFn, isLock } = useLockFn(async () => {
    if (!url.value) return feedback.msgError("请输入网页链接");
    try {
        const data = await webHtmlCapture({
            url: url.value.split("\n").filter(Boolean),
        });
        formData.value = [
            ...data.map((item: any) => ({
                data: [
                    {
                        a: "",
                        q: item.content,
                    },
                ],
                path: item.url,
                name: item.url,
                size: 0,
            })),
            ...formData.value,
        ];
        url.value = "";
    } catch (error) {
        feedback.msgError(error);
    }
});

const handleDeleteStage = (index: number) => {
    formData.value.splice(index, 1);
};
</script>

<style scoped></style>
