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
        popper-class="!rounded-xl"
        class="">
        <template #reference>
            <ElButton type="primary" size="small" class="!px-1" :disabled="disabled">
                <div class="gap-1 flex items-center justify-center">
                    <Icon name="local-icon-fabang"></Icon>
                    <span>AI生成文案</span>
                </div>
            </ElButton>
        </template>
        <div class="rounded-xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Icon name="local-icon-fabang" color="var(--color-primary)" :size="22"></Icon>
                    <span class="text-primary font-bold">AI生成文案</span>
                </div>
                <div
                    class="cursor-pointer leading-[0] hover:bg-token-light-8 rounded-full p-1"
                    @click="showGeneratePrompt = false">
                    <Icon name="el-icon-Close" :size="22"></Icon>
                </div>
            </div>
            <div class="mt-4 relative">
                <ElInput
                    v-model="contentVal"
                    type="textarea"
                    :rows="8"
                    :autosize="{
                        minRows: 8,
                    }"
                    :disabled="isLock"
                    placeholder="请在此输入您需要生成文案的关键词或内容"></ElInput>
                <div class="flex items-center justify-between mt-2">
                    <ElButton
                        type="primary"
                        size="small"
                        class="!px-1"
                        :disabled="isLock || !contentVal"
                        @click.stop="lockSubmit">
                        <div class="flex items-center gap-2">
                            <Icon name="local-icon-fabang" color="#ffffff"></Icon>
                            <span> 开始生成 </span>
                        </div>
                    </ElButton>
                </div>
            </div>
            <div class="mt-4" v-if="chatContentList.length">
                <ElScrollbar ref="scrollRef">
                    <div class="!text-xs max-h-[500px]">
                        <div class="flex flex-col gap-2 content-box">
                            <div
                                v-for="(content, index) in chatContentList"
                                :key="index"
                                class="border border-token-primary rounded-lg p-4">
                                <div>
                                    {{ content }}
                                </div>
                                <div class="justify-end flex mt-2">
                                    <ElButton size="small" @click="useContent(content)">使用文案</ElButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
            </div>
            <div v-if="isReceiving" class="chat-loader mt-2"></div>
        </div>
    </ElPopover>
</template>

<script setup lang="ts">
import { chatPrompt } from "@/api/chat";
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

const handleGeneratePrompt = async () => {
    if (userTokens.value <= 0) {
        feedback.msgPowerInsufficient();
        return;
    }
    try {
        isReceiving.value = true;
        const { reply } = await chatPrompt({
            message: contentVal.value,
            prompt_id: props.promptType,
        });
        chatContentList.value.push(reply);
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

<style scoped></style>
