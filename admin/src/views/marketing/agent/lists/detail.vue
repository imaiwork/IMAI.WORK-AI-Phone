<template>
    <div>
        <el-card shadow="never" class="!border-none">
            <el-page-header content="代理详情" @back="$router.back()" />
        </el-card>
        <el-card shadow="never" class="!border-none mt-[10px]">
            <div class="text-xl font-medium">基本资料</div>
            <el-form ref="formRef" class="ls-form mt-4" :model="formData" label-width="120px">
                <div class="bg-page flex py-5 mb-10 items-center">
                    <div class="basis-40 flex flex-col justify-center items-center">
                        <div class="mb-2 text-tx-regular">用户头像</div>
                        <el-avatar :src="formData.avatar" :size="58" />
                    </div>
                    <div class="basis-40 flex flex-col justify-center items-center">
                        <div class="text-tx-regular">下级人数</div>
                        <div class="mt-2 flex items-center">
                            {{ formData.below_num }}
                            <router-link
                                v-perms="['marketing.agent/lower']"
                                :to="{
                                    path: getRoutePath('marketing.agent/lower'),
                                    query: {
                                        id: route.query.id,
                                    },
                                }">
                                <el-button type="primary" link> 查看下级列表</el-button>
                            </router-link>
                        </div>
                    </div>
                    <div class="basis-40 flex flex-col justify-center items-center">
                        <div class="text-tx-regular">下级代理人数</div>
                        <div class="mt-2 flex items-center">
                            {{ formData.downline_count }}
                        </div>
                    </div>
                </div>
                <el-form-item label="用户信息："> {{ formData.nickname }}</el-form-item>
                <el-form-item label="真实姓名："> {{ formData.real_name || "-" }} </el-form-item>

                <el-form-item label="上级代理：">{{ `${formData.parent_nickname || "系统"}` }} </el-form-item>
                <el-form-item label="代理状态："
                    ><span :class="formData.status == 1 ? 'text-success' : 'text-warning'">{{
                        formData.status == 1 ? "正常" : "禁用"
                    }}</span>
                </el-form-item>
                <el-form-item label="成为代理时间：">
                    {{ formData.become_time }}
                </el-form-item>
            </el-form>

            <el-button
                v-perms="['marketing.agent/setConfig']"
                @click="handleclick(formData.status)"
                v-if="formData.status == 1">
                冻结资格
            </el-button>
            <el-button
                v-perms="['marketing.agent/setConfig']"
                type="primary"
                @click="handleclick(formData.status)"
                v-if="formData.status == 0">
                恢复资格
            </el-button>
        </el-card>
    </div>
</template>
<script setup lang="ts">
import { getRoutePath } from "@/router";
import { setDistribution } from "@/api/user";
import { getAgentUserDetail } from "@/api/marketing/agent";
import feedback from "@/utils/feedback";
const formData = ref<any>({});
const route = useRoute();

const getDetails = async () => {
    const data = await getAgentUserDetail({
        user_id: route.query.id,
    });
    formData.value = data;
};
const handleclick = async (type: number) => {
    await feedback.confirm(`确定${type == 1 ? "冻结" : "恢复"}该用户代理资格？`);
    await setDistribution({ id: route.query.id, field: "status", value: type == 1 ? 0 : 1 });
    getDetails();
};
getDetails();
</script>
