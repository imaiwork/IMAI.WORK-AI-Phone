<template>
    <div class="h-full flex flex-col">
        <!-- 头部区域 -->
        <div class="h-[78px] flex items-center justify-between px-[30px] gap-x-4">
            <div class="flex items-center gap-x-2">
                <img src="@/assets/images/publish_api.png" class="w-[50px] h-[50px] flex-shrink-0" />
                <div>
                    <ElButton type="primary" link @click="handleCall()">调用说明</ElButton>
                    <div>
                        API根地址：
                        <span>{{ path }}</span>
                        <ElButton @click="copy(path)" type="primary" link>复制</ElButton>
                    </div>
                </div>
            </div>
            <div>
                <ElButton type="primary" class="!rounded-full !h-10" @click="handleCreate()">
                    <Icon name="local-icon-add_circle" />
                    <span class="ml-2">创建API</span>
                </ElButton>
                <ElButton class="!rounded-full !h-10" @click="emit('close')">取消</ElButton>
            </div>
        </div>
        <!-- 表格区域 -->
        <div class="grow min-h-0">
            <ElTable
                :data="pager.lists"
                v-loading="pager.loading"
                height="100%"
                stripe
                :header-row-style="{ height: '62px' }"
                :row-style="{ height: '60px' }">
                <ElTableColumn label="APIkey" prop="apikey" min-width="200" show-overflow-tooltip></ElTableColumn>
                <ElTableColumn label="分享名称" prop="name" min-width="180" show-overflow-tooltip></ElTableColumn>
                <ElTableColumn label="最后使用时间" prop="use_time" width="160"></ElTableColumn>
                <ElTableColumn label="操作" width="200">
                    <template #default="{ row }">
                        <ElButton type="primary" link @click="handleSetting(row)"> 用量设置 </ElButton>
                        <ElButton type="danger" link @click="handleDelete(row)"> 删除 </ElButton>
                    </template>
                </ElTableColumn>
            </ElTable>
        </div>
        <!-- 分页 -->
        <div class="my-4 flex justify-center">
            <pagination v-model="pager" layout="prev, pager, next" @change="getLists"></pagination>
        </div>
    </div>
    <!-- 弹窗组件 -->
    <create-popup
        v-if="showCreate"
        ref="createPopupRef"
        :agent-id="props.agentId"
        :type="type"
        :show-chat-type="false"
        @close="showCreate = false"
        @success="getLists" />
    <usage-setting
        v-if="showUsageSetting"
        ref="usageSettingRef"
        :agent-id="agentId"
        :type="type"
        @close="showUsageSetting = false"
        @success="getLists" />
    <call-code v-if="showCallCode" ref="callCodeRef" @close="showCallCode = false" />
</template>

<script setup lang="ts">
import { getPublishList, deletePublish } from "@/api/agent";
import { PublishTypeEnum } from "../../../_enums";
import CreatePopup from "./create-popup.vue";
import UsageSetting from "./usage-setting.vue";
import CallCode from "./call-code.vue";

// 定义props和emits
const props = defineProps<{
    type: PublishTypeEnum;
    agentId: string | number;
}>();
const emit = defineEmits(["close", "success"]);

const nuxtApp = useNuxtApp();
const { copy } = useCopy();

// 分页和查询参数
const queryParams = reactive({
    robot_id: props.agentId,
    type: props.type,
});
const { pager, getLists } = usePaging({
    fetchFun: getPublishList,
    params: queryParams,
});

// API根地址
const path = ref(`${window.location.origin}/api/v1/chat/commonChat`);

// “调用说明”弹窗
const showCallCode = ref(false);
const callCodeRef = shallowRef<InstanceType<typeof CallCode>>();
const handleCall = async () => {
    showCallCode.value = true;
    await nextTick();
    callCodeRef.value?.open();
};

// “创建API”弹窗
const showCreate = ref(false);
const createPopupRef = shallowRef<InstanceType<typeof CreatePopup>>();
const handleCreate = async (row?: any) => {
    showCreate.value = true;
    await nextTick();
    createPopupRef.value.open();
    if (row) {
        createPopupRef.value.setFormData({
            ...row,
            password: row.secret,
        });
    }
};

// “用量设置”弹窗
const showUsageSetting = ref(false);
const usageSettingRef = shallowRef<InstanceType<typeof UsageSetting>>();
const handleSetting = async (row: any) => {
    showUsageSetting.value = true;
    await nextTick();
    usageSettingRef.value?.open();
    usageSettingRef.value?.setFormData(row);
};

/**
 * @description 删除API Key
 */
const handleDelete = async (row: any) => {
    await nuxtApp.$confirm({
        message: "确定删除该数据吗？",
        onConfirm: async () => {
            try {
                await deletePublish({
                    id: row.id,
                    type: props.type,
                });
                feedback.msgSuccess("删除成功");
                getLists(); // 重新获取列表
            } catch (error) {
                feedback.msgWarning("删除失败");
            }
        },
    });
};

// 初始化时获取列表
getLists();
</script>

<style scoped></style>
