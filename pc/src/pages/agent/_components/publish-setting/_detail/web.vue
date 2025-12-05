<template>
    <div class="h-full flex flex-col">
        <!-- 头部区域 -->
        <div class="h-[78px] flex items-center justify-between px-[30px] gap-x-4">
            <div class="flex items-center gap-x-2">
                <img :src="detail.icon" class="w-[50px] h-[50px] flex-shrink-0" />
                <div>
                    <div class="text-[#000000cc]">{{ detail.title }}</div>
                    <div class="mt-1 text-[#00000080]">{{ detail.description }}</div>
                </div>
            </div>
            <div>
                <ElButton type="primary" class="!rounded-full !h-10" @click="handleCreate()">
                    <Icon name="local-icon-add_circle" />
                    <span class="ml-2">创建链接</span>
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
                <ElTableColumn label="访问密码" prop="secret" min-width="120">
                    <template #default="{ row }">
                        {{ row.secret || "-" }}
                    </template>
                </ElTableColumn>
                <ElTableColumn label="对话模式" min-width="120">
                    <template #default="{ row }">
                        <div>
                            {{ row.chat_type == 1 ? "文本对话" : "形象对话" }}
                        </div>
                    </template>
                </ElTableColumn>
                <ElTableColumn label="最后使用时间" prop="use_time" width="160"></ElTableColumn>
                <ElTableColumn label="操作" width="220">
                    <template #default="{ row }">
                        <ElButton type="primary" link @click="handlePoster(row)">生成海报</ElButton>
                        <ElButton type="primary" link @click="handleCopyLink(row)"> 复制链接 </ElButton>
                        <!-- 更多操作 -->
                        <ElPopover :show-arrow="false" popper-class="!w-[130px] !min-w-[130px] !p-[6px] !rounded-xl">
                            <template #reference>
                                <ElButton link>
                                    <Icon name="el-icon-MoreFilled" color="var(--color-primary)"></Icon>
                                </ElButton>
                            </template>
                            <div class="flex flex-col gap-2">
                                <div
                                    class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                    @click="handleCreate(row)">
                                    <Icon name="el-icon-Edit"></Icon>
                                    <span>编辑</span>
                                </div>
                                <div
                                    class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                    @click="handleView(row)">
                                    <span class="flex items-center justify-center">
                                        <Icon name="el-icon-View"></Icon>
                                    </span>
                                    <span>预览</span>
                                </div>
                                <div
                                    class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                    @click="handleSetting(row)">
                                    <Icon name="el-icon-Setting"></Icon>
                                    <span>用量设置</span>
                                </div>
                                <div
                                    class="px-2 py-1 hover:bg-primary-light-9 rounded-lg cursor-pointer flex items-center gap-2"
                                    @click="handleDelete(row)">
                                    <Icon name="el-icon-Delete"></Icon>
                                    <span>删除</span>
                                </div>
                            </div>
                        </ElPopover>
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
        :agent-id="agentId"
        :type="type"
        show-chat-type
        @close="showCreate = false"
        @success="getLists" />
    <usage-setting
        v-if="showUsageSetting"
        ref="usageSettingRef"
        :agent-id="agentId"
        :type="type"
        @close="showUsageSetting = false"
        @success="getLists" />
    <poster-setting v-if="showPosterSetting" ref="posterSettingRef" @close="showPosterSetting = false" />
</template>

<script setup lang="ts">
import { getPublishList, deletePublish } from "@/api/agent";
import PublishCodeImage from "@/assets/images/publish_code.png";
import PublishPosterImage from "@/assets/images/publish_poster.png";
import { PublishTypeEnum } from "../../../_enums";
import CreatePopup from "./create-popup.vue";
import UsageSetting from "./usage-setting.vue";
import PosterSetting from "./poster-setting.vue";

// 定义props和emits
const props = defineProps<{
    type: PublishTypeEnum;
    agentId: string | number;
}>();
const emit = defineEmits(["close", "success"]);

const { copy } = useCopy();
const nuxtApp = useNuxtApp();

// 根据发布类型显示不同的标题、图标和描述
const detail = computed(() => {
    switch (props.type) {
        case PublishTypeEnum.WEB:
            return {
                title: "网页",
                icon: PublishCodeImage,
                description:
                    "可以直接分享该模型给其他用户去进行对话，对方无需登录即可直接进行对话。注意，这个功能会消耗你账号的问答条数。请保管好链接和密码。",
            };
        case PublishTypeEnum.POSTER:
            return {
                title: "发布海报",
                icon: PublishPosterImage,
                description: "将海报发布到指定环境",
            };
        default:
            return { title: "", icon: "", description: "" };
    }
});

// 分页和查询
const queryParams = reactive({
    robot_id: props.agentId,
    type: props.type,
});
const { pager, getLists } = usePaging({
    fetchFun: getPublishList,
    params: queryParams,
});

// “创建/编辑链接”弹窗
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

/**
 * @description 生成用于复制或预览的链接
 * @param row - 表格行数据
 * @returns {string} - 完整的URL
 * @summary 使用URL hash传递名称和密码，避免敏感信息出现在URL路径中，仅用于前端展示。
 */
const getLink = (row: any) => {
    return `${location.origin}${getBaseUrl()}/chat/${row.apikey} #${row.name} ${
        row.secret ? `密码: ${row.secret}` : ""
    }`;
};

// “生成海报”弹窗
const showPosterSetting = ref(false);
const posterSettingRef = shallowRef<InstanceType<typeof PosterSetting>>();
const handlePoster = async (row: any) => {
    showPosterSetting.value = true;
    await nextTick();
    posterSettingRef.value?.open();
    posterSettingRef.value?.setFormData(row);
};

/**
 * @description 复制链接
 */
const handleCopyLink = async (row: any) => {
    const link = getLink(row);
    await copy(link);
};

/**
 * @description 预览链接
 */
const handleView = async (row: any) => {
    const link = getLink(row);
    window.open(link);
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
 * @description 删除链接
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
