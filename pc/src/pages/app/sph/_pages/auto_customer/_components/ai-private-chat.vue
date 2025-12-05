<template>
    <popup
        ref="popupRef"
        async
        width="500px"
        confirm-button-text=""
        cancel-button-text=""
        style="
            padding: 18px;
            border-radius: 20px;
            background-color: var(--app-bg-color-3);
            box-shadow: 0 0 0 1px #2a2a2a;
        "
        :append-to-body="false"
        :show-close="false">
        <div class="-my-4">
            <div class="absolute w-6 h-6 top-[18px] right-[18px] z-[22]" @click="close">
                <close-btn />
            </div>
            <div class="text-[15px] text-white font-bold">AI提示词设置（私信内容）</div>
            <div class="mt-4">
                <ElInput
                    v-model="formData.content"
                    type="textarea"
                    placeholder="请输入 AI 提示词"
                    resize="none"
                    show-word-limit
                    :maxlength="1000"
                    :rows="6" />
            </div>
            <div class="mt-4">
                <ElButton type="primary" class="!rounded-full w-full !h-[50px]" @click="handleSave">立即保存</ElButton>
            </div>
            <div class="flex justify-center mt-4">
                <ElButton link type="primary" @click="handleDefaultPrompt"> 一键填入默认提示词 </ElButton>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { CopywritingTypeEnum } from "@/pages/app/_enums/chatEnum";
import { useAppStore } from "@/stores/app";
const emit = defineEmits(["close", "confirm"]);

const appStore = useAppStore();

const chatPrompt = computed(() =>
    appStore.scenePrompt?.find((item) => item.id == CopywritingTypeEnum.AI_SPH_PRIVATE_CHAT)
);

const popupRef = ref();

const formData = reactive({
    content: "",
});

const handleDefaultPrompt = () => {
    formData.content = chatPrompt.value?.prompt_text || "";
};

const handleSave = () => {
    close();
    emit("confirm", formData.content);
};

const open = () => {
    popupRef.value.open();
};

const close = () => {
    emit("close");
};

defineExpose({
    open,
    setFormData: (data) => setFormData(data, formData),
});
</script>
