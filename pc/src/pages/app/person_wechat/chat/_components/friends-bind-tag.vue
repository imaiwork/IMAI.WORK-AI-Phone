<template>
    <popup
        ref="tagPopRef"
        title="添加标签"
        async
        width="487px"
        confirm-button-text="确认添加"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <ElForm ref="tagFormRef" :model="tagForm" label-position="top" :rules="tagFormRules">
            <ElFormItem label="请选择标签" prop="tag_ids">
                <ElSelect v-model="tagForm.tag_ids" placeholder="请选择标签" multiple filterable clearable>
                    <ElOption v-for="item in wechatTagLists" :key="item.id" :label="item.tag_name" :value="item.id" />
                </ElSelect>
            </ElFormItem>
        </ElForm>
    </popup>
</template>

<script setup lang="ts">
import Popup from "@/components/popup/index.vue";
import { type FormInstance } from "element-plus";
import useHandle from "../../_hooks/useHandle";

const emit = defineEmits<{
    (e: "success"): void;
    (e: "close"): void;
}>();

const { wechatTagLists, addFriendTag, getWechatTagLists } = useHandle();

const tagPopRef = ref<InstanceType<typeof Popup> | null>(null);
const showTagPop = ref<boolean>(false);
const tagForm = reactive({
    tag_ids: [],
});
const tagFormRef = ref<FormInstance>();
const tagFormRules = {
    tag_ids: [{ required: true, message: "请选择标签" }],
};

const open = async () => {
    tagPopRef.value?.open();
    getWechatTagLists();
};

const close = () => {
    emit("close");
};

const { lockFn, isLock } = useLockFn(async () => {
    await tagFormRef.value?.validate();
    try {
        await addFriendTag({
            tag_ids: tagForm.tag_ids,
        });
        showTagPop.value = false;
        tagForm.tag_ids = [];
        tagPopRef.value?.close();
        feedback.msgSuccess("添加标签成功");
    } catch (error) {
        feedback.msgError(error);
    }
});

defineExpose({
    open,
});
</script>

<style scoped></style>
