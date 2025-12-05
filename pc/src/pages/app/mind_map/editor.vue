<template>
    <div class="flex h-full w-full flex-col items-center">
        <div class="relative flex w-full grow overflow-hidden">
            <div class="absolute right-2 top-2 z-[888]">
                <ElButton
                    type="primary"
                    round
                    class="flex w-full items-center justify-center gap-1.5"
                    :disabled="isLock || !formData.reply"
                    @click="handleExport()">
                    导出思维导图
                </ElButton>
            </div>
            <div class="w-[350px] flex-shrink-0 justify-center min-h-0 bg-white pt-4">
                <div class="h-full grow">
                    <ElScrollbar>
                        <div class="flex h-full flex-col px-5 pt-2 gap-y-4">
                            <ElForm :model="formData" label-position="top">
                                <ElFormItem label="输入内容即可生成思维导图">
                                    <ElInput
                                        v-model="formData.ask"
                                        type="textarea"
                                        :rows="15"
                                        resize="none"
                                        placeholder=""></ElInput>
                                    <ElButton
                                        class="w-full !h-10 mt-4"
                                        round
                                        type="primary"
                                        :disabled="!formData.ask"
                                        :loading="isLock"
                                        @click="lockHandleGenerate()"
                                        >立即生成
                                    </ElButton>
                                </ElFormItem>
                                <ElFormItem>
                                    <template #label>
                                        <div class="flex items-center justify-between gap-x-2">
                                            <span>内容大纲</span>
                                            <ElButton type="primary" link @click="handleExample()">试试示例</ElButton>
                                        </div>
                                    </template>
                                    <ElInput
                                        v-model="formData.reply"
                                        type="textarea"
                                        disabled
                                        :rows="25"
                                        resize="none"
                                        placeholder=""></ElInput>
                                </ElFormItem>
                            </ElForm>
                        </div>
                    </ElScrollbar>
                </div>
            </div>
            <div class="grow w-3/5 p-4 justify-center lg:border-l border-token-border-medium overflow-hidden relative">
                <div class="flex-grow pb-5 h-full">
                    <div
                        class="h-full flex flex-col gap-y-2"
                        :class="{
                            'fixed top-0 left-0 w-full z-[8888] bg-white': isFullscreen,
                        }">
                        <div class="grow overflow-hidden relative">
                            <div ref="toolbarContainer"></div>
                            <svg ref="mindMapContainer" class="w-full h-full"></svg>
                        </div>
                    </div>
                </div>
                <div
                    class="absolute top-0 left-0 w-full h-full flex flex-col items-center justify-center bg-white z-[88888]"
                    v-if="isLock">
                    <Loader />
                    <div class="text-sm text-gray-500 mt-10">正在生成思维导图...</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { mindMapDetail, mindMapEditChat } from "@/api/mind_map";
import { chatPrompt } from "@/api/chat";
import { ElFormItem, ElScrollbar } from "element-plus";
import { useUserStore } from "@/stores/user";
import { useMindMap } from "@/composables/useMindMap";
import { TokensSceneEnum } from "@/enums/appEnums";
import { mindMapExample } from "@/config/common";
import { ScenePromptEnum } from "../_enums/chatEnum";
const route = useRoute();
const router = useRouter();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);
const tokensValue = userStore.getTokenByScene(TokensSceneEnum.MIND_MAP)?.score;

const formData = reactive({
    id: "",
    reply: "",
    ask: "",
    prompt_id: ScenePromptEnum.AI_MIND_MAP,
});

const mindMapContainer = shallowRef(null);
const { markmap, toolbarContainer, isFullscreen, mindMapInit, mindMapFit, mindMapExportAsPNG } = useMindMap();

const handleExample = async () => {
    formData.reply = mindMapExample;
    markmap.value && markmap.value.destroy();
    await nextTick();
    initMindMap();
};

const handleExport = () => {
    mindMapExportAsPNG(mindMapContainer.value);
};

const { lockFn: lockHandleGenerate, isLock } = useLockFn(async () => {
    if (userTokens.value <= tokensValue.value) {
        feedback.msgPowerInsufficient();
        return;
    }
    markmap.value && markmap.value.destroy();
    try {
        const data = formData.id
            ? await mindMapEditChat({
                  id: formData.id,
                  message: formData.ask,
              })
            : await chatPrompt({
                  message: formData.ask,
                  prompt_id: ScenePromptEnum.AI_MIND_MAP,
              });
        formData.reply = data.reply;
        formData.id = data.id;
        router.replace({
            path: route.path,
            query: {
                id: data.id,
            },
        });
        userStore.getUser();

        initMindMap();
    } catch (error) {
        feedback.msgError(error || "发生错误");
    }
});

const getDetail = async () => {
    const data = await mindMapDetail({
        id: route.query.id,
    });
    Object.keys(formData).forEach((key) => {
        // @ts-ignore
        formData[key] = data[key];
    });
    initMindMap();
};

const initMindMap = async () => {
    mindMapInit(mindMapContainer.value);
    await nextTick();
    // formData.reply 可能包含```markdown 和```，需要去除
    mindMapFit(formData.reply.replace(/```markdown/g, "").replace(/```/g, ""));
};

onMounted(() => {
    if (route.query.id) {
        getDetail();
    }
});

definePageMeta({
    layout: "base",
    title: "AI思维导图",
});
</script>

<style lang="scss" scoped>
#mindMapContainer * {
    margin: 0;
    padding: 0;
}
:deep(.el-form-item__label) {
    @apply text-base;
}
</style>
