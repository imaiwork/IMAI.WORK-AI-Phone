<template>
    <div class="h-full flex rounded-lg overflow-hidden">
        <template v-if="!isEdit">
            <div class="w-[178px] bg-white border-r border-r-[#E8E8E8] flex-shrink-0">
                <div class="flex flex-col gap-y-4 p-4">
                    <div
                        v-for="(item, index) in getSocialPlatformList"
                        :key="index"
                        class="flex items-center gap-x-3 px-4 hover:bg-primary-light-9 py-1.5 rounded-lg cursor-pointer"
                        :class="{ 'bg-primary-light-9': currentSocialPlatform === item.type }"
                        @click="handleChangeSocialPlatform(item.type)">
                        <img :src="item.icon" class="w-6 h-6" />
                        <div>{{ item.name }}</div>
                    </div>
                </div>
            </div>
            <div class="flex-1 bg-white flex flex-col py-4 overflow-hidden">
                <div class="px-4 flex items-center justify-between">
                    <ElButton text @click="getAccountList()">
                        <Icon name="el-icon-Refresh"></Icon>
                        <span>刷新</span>
                    </ElButton>
                    <div>
                        <ElInput
                            v-model="queryParams.name"
                            placeholder="搜索账号信息"
                            prefix-icon="el-icon-Search"
                            clearable
                            @clear="resetAccountParams()"
                            @keyup.enter="getAccountList()" />
                    </div>
                </div>
                <div class="grow min-h-0 mt-4">
                    <ElTable
                        :data="accountPager.lists"
                        v-loading="accountPager.loading"
                        height="100%"
                        stripe
                        :row-style="{ height: '60px' }"
                        :header-cell-style="{ height: '63px' }">
                        <ElTableColumn label="头像">
                            <template #default="{ row }">
                                <ElAvatar :src="row.avatar"></ElAvatar>
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="账号/昵称" prop="account" min-width="120">
                            <template #default="{ row }">
                                <div>
                                    <div>{{ row.account }}</div>
                                    <div>({{ row.nickname }})</div>
                                </div>
                            </template>
                        </ElTableColumn>
                        <ElTableColumn prop="device_code" label="所属设备" />
                        <ElTableColumn prop="robot_name" label="接管机器人">
                            <template #default="{ row }">
                                <div>{{ row.robot_name || "-" }}</div>
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="接管类型" width="80">
                            <template #default="{ row }"> 私聊 </template>
                        </ElTableColumn>
                        <ElTableColumn prop="name" label="接管状态">
                            <template #default="{ row }">
                                <ElSwitch
                                    v-if="row.type == AppTypeEnum.XHS"
                                    v-model="row.open_ai"
                                    :active-value="1"
                                    :inactive-value="0"
                                    @change="changeOpenAi(row)" />
                                <div v-else>-</div>
                            </template>
                        </ElTableColumn>
                        <ElTableColumn prop="name" label="操作" width="100px" fixed="right">
                            <template #default="{ row }">
                                <ElButton
                                    v-if="row.type == AppTypeEnum.XHS"
                                    link
                                    type="primary"
                                    @click="handleEdit(row)">
                                    配置
                                </ElButton>
                                <div v-else>-</div>
                            </template>
                        </ElTableColumn>
                        <template #empty>
                            <ElEmpty description="暂无数据" />
                        </template>
                    </ElTable>
                </div>
                <div class="flex justify-end px-4 mt-4">
                    <pagination v-model="accountPager" @change="getAccountList"></pagination>
                </div>
            </div>
        </template>
        <template v-else>
            <AccountSetting @close="reset"></AccountSetting>
        </template>
    </div>
</template>

<script setup lang="ts">
import { changeAccountStatus } from "@/api/service";
import { AppTypeEnum } from "@/enums/appEnums";
import { useSocialPlatform } from "@/composables/useSocialPlatform";
import useAccount from "../../_hooks/useAccount";
import AccountSetting from "./_components/account-setting.vue";
const router = useRouter();
const route = useRoute();

const { socialPlatformList, currentSocialPlatform } = useSocialPlatform();
const { accountPager, queryParams, getAccountList, resetAccountPage, resetAccountParams } = useAccount({
    type: currentSocialPlatform.value,
});

const getSocialPlatformList = computed(() => {
    return socialPlatformList.filter((item) => item.show);
});

const handleChangeSocialPlatform = (type: AppTypeEnum) => {
    currentSocialPlatform.value = type;
    queryParams.type = type;
    resetAccountPage();
};

const isEdit = ref(false);

const changeOpenAi = async (row: any) => {
    try {
        await changeAccountStatus({
            account: row.account,
            open_ai: row.open_ai, //AI总功能开关 0：关闭 1：开启
        });
        feedback.msgSuccess("操作成功");
    } catch (error) {
        feedback.msgError("操作失败");
    }
    getAccountList();
};

const handleEdit = (row: any) => {
    isEdit.value = true;
    router.replace({
        query: {
            ...route.query,
            id: row.id,
            account: row.account,
            app_type: currentSocialPlatform.value,
        },
    });
};

const reset = () => {
    isEdit.value = false;
    router.replace({
        query: {
            ...route.query,
            id: undefined,
            account: undefined,
        },
    });
    resetAccountPage();
};

onMounted(() => {
    const id = route.query.id;
    if (id) {
        isEdit.value = true;
    } else {
        getAccountList();
    }
});
</script>

<style scoped></style>
