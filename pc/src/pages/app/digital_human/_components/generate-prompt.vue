<template>
    <ElPopover
        v-model:visible="showGeneratePrompt"
        placement="right"
        trigger="click"
        width="375"
        :show-arrow="false"
        :disabled="disabled"
        :popper-options="{
            modifiers: [
                {
                    name: 'offset',
                    options: {
                        offset: [-90, 40],
                    },
                },
            ],
        }"
        @hide="handleHide"
        popper-class="!rounded-xl !bg-app-bg-2 !shadow-none !border-none">
        <template #reference>
            <ElButton
                type="primary"
                size="small"
                color="rgba(255, 255, 255, 0.1)"
                class="!px-3 !border !border-[var(--app-border-color-1)]"
                :disabled="disabled">
                <div class="gap-1 flex items-center justify-center">
                    <Icon name="local-icon-fabang" color="#ffffff"></Icon>
                    <span class="text-white text-xs">AI生成文案</span>
                </div>
            </ElButton>
        </template>
        <div class="rounded-xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-5 h-5 flex items-center justify-center rounded bg-[#ffffff0d]">
                        <Icon name="local-icon-robot2"></Icon>
                    </span>
                    <span class="text-white text-[20px] font-bold">智能（AI）生成文案</span>
                </div>
                <div
                    class="absolute right-4 top-4 w-6 h-6 rounded-full bg-[#ffffff0d] flex items-center justify-center cursor-pointer hover:bg-[#ffffff1a]"
                    @click="showGeneratePrompt = false">
                    <Icon name="el-icon-Close" color="#8B9199"></Icon>
                </div>
            </div>
            <div class="mt-4 relative">
                <div class="mb-[10px]">
                    <span class="text-[#ff3c26]">*</span>
                    <span class="text-white font-bold">输入文案</span>
                </div>
                <ElInput
                    v-model="contentVal"
                    class="content-input"
                    type="textarea"
                    input-style="color: #ffffff"
                    :rows="8"
                    :autosize="{
                        minRows: 8,
                    }"
                    :disabled="isLock"
                    placeholder="请在此输入您需要生成文案的关键词或内容 ..."></ElInput>
                <div class="flex items-center justify-between mt-2">
                    <ElSegmented
                        v-model="currentPromptValue"
                        class="w-full !bg-app-bg-1 !h-11 !rounded-full"
                        :props="{}"
                        :options="getPromptList"></ElSegmented>
                </div>
                <div class="flex justify-center">
                    <ElButton
                        type="primary"
                        size="small"
                        class="!rounded-full !h-[50px] !w-[298px] mt-[18px]"
                        :disabled="isLock || !contentVal"
                        @click.stop="lockSubmit">
                        立即生成
                    </ElButton>
                </div>
            </div>
            <div class="mt-4" v-if="chatContentList.length">
                <ElScrollbar ref="scrollRef">
                    <div class="!text-xs max-h-[30vh]">
                        <div class="flex flex-col gap-2 content-box">
                            <div
                                v-for="(content, index) in chatContentList"
                                :key="index"
                                class="border border-[var(--app-border-color-1)] bg-app-bg-1 leading-[22px] rounded-lg p-4">
                                <div class="text-white">
                                    {{ content }}
                                </div>
                                <div class="justify-end flex mt-2">
                                    <ElButton type="primary" size="small" @click="useContent(content)"
                                        >使用文案</ElButton
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
            </div>
            <div v-if="isReceiving" class="chat-loader mt-2 !text-white"></div>
        </div>
    </ElPopover>
</template>

<script setup lang="ts">
import { generatePrompt } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import { CopywritingTypeEnum } from "@/pages/app/_enums/chatEnum";

const props = withDefaults(
    defineProps<{
        promptType: CopywritingTypeEnum;
        maxSize?: number;
        disabled?: boolean;
    }>(),
    {
        promptType: CopywritingTypeEnum.AI_DIGITAL_HUMAN_COPYWRITING,
        maxSize: 0,
        disabled: false,
    }
);

const emit = defineEmits(["use-content"]);

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);
const showGeneratePrompt = ref<boolean>(false);

// 生成的文案
const contentVal = ref<string>("");
const chatContentList = ref<any[]>([]);
const isReceiving = ref(false);

const promptList = [
    { id: 1, label: "短", value: 150, disabled: false },
    { id: 2, label: "中", value: 300, disabled: false },
    { id: 3, label: "长", value: 1000, disabled: false },
];
const currentPromptValue = ref<any>(promptList[0].value);

const getPromptList = computed(() => {
    promptList.forEach((item) => {
        if (item.value > props.maxSize) {
            item.disabled = true;
        } else {
            item.disabled = false;
        }
    });
    return promptList;
});

const handleHide = () => {
    currentPromptValue.value = promptList[0].value;
};

const handleGeneratePrompt = async () => {
    if (userTokens.value <= 0) {
        feedback.msgPowerInsufficient();
        return;
    }
    try {
        isReceiving.value = true;
        const { content } = await generatePrompt({
            keywords: contentVal.value,
            number: currentPromptValue.value,
        });
        chatContentList.value.push(content);
        contentVal.value = "";
    } catch (error) {
        feedback.msgError(error || "生成失败，请重试");
    } finally {
        isReceiving.value = false;
        userStore.getUser();
        setTimeout(() => {
            scrollBottom();
        }, 500);
    }
};

const scrollRef = shallowRef();
const scrollBottom = () => {
    scrollRef.value?.scrollTo(document.querySelector(".content-box").clientHeight);
};

const useContent = (content: string) => {
    emit("use-content", content);
};

const { lockFn: lockSubmit, isLock } = useLockFn(handleGeneratePrompt);
</script>

<style scoped lang="scss">
:deep(.content-input) {
    .el-textarea__inner {
        background-color: #121212;
        box-shadow: 0 0 0 1px #2a2a2a;
        padding: 15px;
        &::placeholder {
            color: #ffffff4d;
        }
    }
}
:deep(.el-segmented__item) {
    color: #ffffff;
    border-radius: 100px;
    &.is-active {
        background-color: var(--app-bg-color-1);
        border-color: var(--app-bg-color-1);
    }

    &:not(.is-disabled):not(.is-selected):hover {
        background-color: #ffffff0d;
        color: #ffffff;
    }
}
:deep(.el-segmented__item-selected) {
    border-radius: 100px;
}
</style>
