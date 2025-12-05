<template>
    <div>
        <el-card shadow="never" class="!border-none">
            <div class="text-xl font-medium mb-[20px]">充值设置</div>
            <el-form ref="formRef" :model="formData" :rules="rules">
                <el-form-item label="小程序IOS是否显示" prop="is_ios_open">
                    <div>
                        <el-switch v-model="formData.is_ios_open" :active-value="1" :inactive-value="0" />
                        <div class="form-tips">开启后，小程序IOS端将显示充值入口</div>
                    </div>
                </el-form-item>
            </el-form>
            <div>
                <el-button
                    v-perms="['finance.marketing.recharge/setConfig']"
                    type="primary"
                    :loading="isLock"
                    @click="lockSubmit"
                    >保存</el-button
                >
            </div>
        </el-card>
        <el-card shadow="never" class="!border-none mt-4">
            <div v-perms="['finance.marketing.rp/add:edit']">
                <router-link :to="getRoutePath('finance.marketing.rp/add:edit')">
                    <el-button type="primary">
                        <template #icon>
                            <icon name="el-icon-Plus" />
                        </template>
                        新增充值套餐
                    </el-button>
                </router-link>
            </div>
            <el-table size="large" :data="pager.lists" v-loading="pager.loading" class="mt-4">
                <el-table-column label="套餐价格" min-width="120">
                    <template #default="{ row }"> ¥{{ row.price }}</template>
                </el-table-column>
                <el-table-column label="套餐名称" prop="name" min-width="120"></el-table-column>
                <el-table-column label="算力值" prop="package_info.tokens" min-width="120"> </el-table-column>
                <el-table-column label="排序" prop="sort"> </el-table-column>
                <el-table-column label="操作" width="120" fixed="right">
                    <template #default="{ row }">
                        <el-button type="primary" link>
                            <router-link
                                v-perms="['finance.marketing.rp/add:edit']"
                                :to="{
                                    path: getRoutePath('finance.marketing.rp/add:edit'),
                                    query: {
                                        id: row.id,
                                        mode: 'edit',
                                    },
                                }">
                                编辑
                            </router-link>
                        </el-button>
                        <el-button v-perms="['recharge.package/del']" type="danger" link @click="handleDelete(row.id)">
                            删除
                        </el-button>
                    </template>
                </el-table-column>
            </el-table>
        </el-card>
    </div>
</template>
<script setup lang="ts">
import {
    getRechargeLists,
    rechargeDelete,
    getRechargeSettingConfig,
    rechargeSettingConfig,
} from "@/api/marketing/recharge";
import { useLockFn } from "@/hooks/useLockFn";
import { usePaging } from "@/hooks/usePaging";
import { getRoutePath } from "@/router";
import feedback from "@/utils/feedback";

const formData = reactive({
    is_ios_open: 0,
});

const rules = {};

const getRechargeConfig = async () => {
    const data = await getRechargeSettingConfig();
    formData.is_ios_open = data.is_ios_open;
};

const setFormData = async (row: any) => {
    for (const key in formData) {
        if (row[key] != null && row[key] != undefined) {
            //@ts-ignore
            formData[key] = row[key];
        }
    }
};

const { isLock, lockFn: lockSubmit } = useLockFn(async () => {
    await rechargeSettingConfig(formData);
});

const { pager, getLists } = usePaging({
    fetchFun: getRechargeLists,
});

//删除
const handleDelete = async (id: number) => {
    await feedback.confirm("确定要删除？");
    await rechargeDelete({ id });
    getLists();
};

getRechargeConfig();
getLists();
</script>
