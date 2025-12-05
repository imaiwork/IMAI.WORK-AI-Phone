<template>
    <div class="h-full flex flex-col bg-white rounded-xl overflow-hidden">
        <div class="h-[48px] bg-primary text-white text-2xl flex items-center px-4">策略设置</div>
        <div class="grow min-h-0 px-10 mt-5">
            <div class="flex flex-wrap gap-x-[60px] w-full">
                <div class="w-[45%]">
                    <div class="flex flex-col gap-y-4">
                        <div class="flex items-center gap-2">
                            <span class="w-[4px] h-[14px] bg-primary"></span>
                            <span class="text-lg">自动同意好友申请</span>
                        </div>
                        <div class="mt-2">
                            <div class="text-[#9E9E9E] mb-2">是否开启</div>
                            <ElSwitch v-model="formData.is_enable" :active-value="1" :inactive-value="0" />
                        </div>
                        <div class="mt-2">
                            <div class="text-[#9E9E9E] mb-2">每日添加数量</div>
                            <div class="flex items-center gap-2">
                                <ElInputNumber
                                    v-model="formData.accept_numbers"
                                    :precision="0"
                                    :min="0"
                                    size="small" />个
                            </div>
                        </div>
                        <div class="mt-2">
                            <div class="text-[#9E9E9E] mb-2">每添加一个间隔时间</div>
                            <div class="flex items-center gap-2">
                                <ElInputNumber
                                    v-model="formData.add_interval_time"
                                    :precision="0"
                                    :min="0"
                                    size="small" />分钟
                            </div>
                        </div>
                        <div class="mt-2 w-full">
                            <div class="text-[#9E9E9E] mb-2">执行微信号</div>
                            <ElSelect v-model="formData.wechat_ids" class="!w-[70%]" multiple clearable filterable>
                                <ElOption
                                    v-for="item in optionsData.wechatLists"
                                    :key="item.id"
                                    :label="item.wechat_nickname"
                                    :value="item.wechat_id"></ElOption>
                            </ElSelect>
                        </div>
                    </div>
                </div>
                <div class="w-[45%]">
                    <div class="flex flex-col gap-y-4">
                        <div class="flex items-center gap-2">
                            <span class="w-[4px] h-[14px] bg-primary"></span>
                            <span class="text-lg">同意申请的好友类型</span>
                        </div>
                        <div class="mt-2">
                            <div class="text-[#9E9E9E] mb-2">自动同意策略</div>
                            <ElSelect v-model="acceptSource" class="!w-[70%]" @change="handleAcceptSourceChange">
                                <ElOption
                                    v-for="item in acceptSourceOptions"
                                    :key="item.id"
                                    :label="item.label"
                                    :value="item.id"></ElOption>
                            </ElSelect>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex justify-center my-4">
            <ElButton type="primary" class="w-[120px] !h-[40px]" :loading="isLock" @click="lockConfirm">
                保存
            </ElButton>
        </div>
    </div>
</template>

<script setup lang="ts">
import { autoFriendPassStrategy, autoFriendPassStrategyInfo, getWeChatLists } from "@/api/person_wechat";

const formData = reactive({
    is_enable: false, //是否开启
    accept_numbers: 0, //每日添加数量
    add_interval_time: 0, //每添加一个间隔时间
    wechat_ids: [], //执行微信号
    accept_type: 0, //同意申请的好友类型
    accept_source: [], //命中消息来源
});

const acceptSource = ref<number>();

const { optionsData } = useDictOptions<{
    wechatLists: any[];
}>({
    wechatLists: {
        api: getWeChatLists,
        params: { page_size: 999 },
        transformData: (data: any) => data.lists,
    },
});

const acceptSourceOptions = [
    {
        id: 0,
        label: "不限",
        value: [],
    },
    {
        id: 1,
        value: ["1000003"],
        label: "通过搜索微信号添加",
    },
    {
        id: 2,
        value: ["1000008", "1000014"],
        label: "通过群聊添加",
    },
    {
        id: 3,
        value: ["1000015"],
        label: "通过搜索手机号添加",
    },
    {
        id: 4,
        value: ["1000017"],
        label: "通过名片分享添加",
    },
    {
        id: 5,
        value: ["1000030"],
        label: "通过扫一扫添加",
    },
];

const handleAcceptSourceChange = (value: number) => {
    const item = acceptSourceOptions.find((item) => item.id === value);
    formData.accept_source = item?.value || [];
};

const handleConfirm = async () => {
    try {
        await autoFriendPassStrategy(formData);
        feedback.msgSuccess("保存成功");
        getRobotReplyStrategyFn();
    } catch (error) {
        feedback.msgError(error || "保存失败");
    }
};
const { lockFn: lockConfirm, isLock } = useLockFn(handleConfirm);

const getRobotReplyStrategyFn = async () => {
    const data = await autoFriendPassStrategyInfo();
    setFormData(data);
};

const setFormData = async (data: Record<any, any>) => {
    for (const key in formData) {
        if (data[key] != null && data[key] != undefined) {
            //@ts-ignore
            formData[key] = data[key];
        }
    }
    acceptSource.value = acceptSourceOptions.find((item) => {
        return JSON.stringify(item.value) === JSON.stringify(formData.accept_source);
    })?.id;
};

onMounted(() => {
    getRobotReplyStrategyFn();
});
</script>

<style scoped></style>
