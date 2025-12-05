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
            <div class="text-[15px] text-white font-bold">AI添加检索关键词</div>
            <div class="mt-4">
                <ElSelect
                    v-model="formData.targetCount"
                    placeholder="请选择生成数量"
                    class="!h-11"
                    popper-class="dark-select-popper "
                    :show-arrow="false">
                    <ElOption
                        v-for="item in [10, 20, 30, 40, 50, 60, 70, 80, 90, 100]"
                        :key="item"
                        :label="`${item}个`"
                        :value="item" />
                </ElSelect>
            </div>
            <div class="mt-4">
                <div class="text-white mb-3">生成相关内容</div>
                <ElInput
                    v-model="formData.keyword"
                    type="textarea"
                    placeholder="请输入需要检索的相关方向，AI将自动为您生成相关检索词"
                    resize="none"
                    :rows="10" />
            </div>
            <div class="mt-4">
                <ElButton type="primary" class="!rounded-full w-full !h-[50px]" :loading="isLock" @click="lockFn"
                    >立即生成(消耗{{ getTokens }}算力)</ElButton
                >
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { useUserStore } from "@/stores/user";
import { getAiKeywords } from "@/api/sph";
import { CreateTypeEnum } from "@/pages/app/sph/_enums";
import { TokensSceneEnum } from "@/enums/appEnums";

const props = defineProps({
    type: {
        type: Number,
        default: CreateTypeEnum.VIDEO,
    },
});

const emit = defineEmits(["close", "success"]);

const userStore = useUserStore();
const getTokens = computed(() => {
    const score = userStore.getTokenByScene(TokensSceneEnum.SPH_AI_CLUE)?.score;
    return score * formData.targetCount;
});

const popupRef = ref();

const formData = reactive({
    targetCount: 30,
    keyword: "",
    channelVersion: props.type,
});

const open = () => {
    popupRef.value.open();
    formData.channelVersion = props.type;
};

const close = () => {
    emit("close");
};

const { lockFn, isLock } = useLockFn(async () => {
    if (userStore.userTokens <= getTokens.value) {
        feedback.msgPowerInsufficient();
        return;
    }
    if (!formData.keyword) {
        feedback.msgWarning("请输入您想获取的线索方向");
        return;
    }
    try {
        let data = await getAiKeywords(formData);
        if (data && data.length > 0) {
            data = data.filter((item: any) => item.indexOf("=") == -1).map((item: any) => item.trim());
        }
        userStore.getUser();
        emit("success", data);
        close();
    } catch (error) {
        feedback.msgError(error || "操作失败");
    }
});

defineExpose({
    open,
});
</script>
