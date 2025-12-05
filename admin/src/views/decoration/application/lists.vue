<template>
	<div>
		<el-card class="!border-none" shadow="never">
			<el-form
				ref="formRef"
				class="mb-[-16px]"
				:model="queryParams"
				:inline="true">
				<el-form-item label="应用名称">
					<el-input
						class="w-[280px]"
						v-model="queryParams.name"
						placeholder="请输入应用名称"
						clearable
						@keyup.enter="resetPage" />
				</el-form-item>
				<el-form-item>
					<el-button type="primary" @click="resetPage"
						>查询</el-button
					>
					<el-button @click="resetParams">重置</el-button>
				</el-form-item>
			</el-form>
		</el-card>
		<el-card class="!border-none mt-4" shadow="never">
			<el-table
				size="large"
				v-loading="pager.loading"
				:data="pager.lists">
				<el-table-column label="ID" prop="id" width="80" />
				<el-table-column label="应用名称" prop="name" min-width="180" />
				<el-table-column label="图标" min-width="100">
					<template #default="{ row }">
						<image-contain
							radius="50%"
							class="flex-none"
							v-if="row.pic"
							:src="row.pic"
							:width="48"
							:height="48"
							:preview-src-list="[row.pic]"
							preview-teleported
							fit="contain" />
					</template>
				</el-table-column>
				<el-table-column label="是否标新" width="100">
					<template #default="{ row }">
						{{ row.is_new ? "是" : "否" }}
					</template>
				</el-table-column>
				<el-table-column
					label="状态"
					min-width="100"
					v-perms="['decoration.application/changeStatus']">
					<template #default="{ row }">
						<el-switch
							v-model="row.show_status"
							:active-value="1"
							:inactive-value="0"
							@change="changeStatus(row.id)" />
					</template>
				</el-table-column>
				<el-table-column label="排序" prop="sort" min-width="100" />
				<el-table-column label="操作" width="160" fixed="right">
					<template #default="{ row }">
						<el-button type="primary" link>
							<router-link
								:to="{
									path: getRoutePath(
										'decoration.application/edit'
									),
									query: {
										id: row.id,
										name: row.name,
									},
								}"
								v-perms="['decoration.application/edit']">
								编辑
							</router-link>
						</el-button>
					</template>
				</el-table-column>
			</el-table>
		</el-card>
	</div>
</template>
<script lang="ts" setup>
import {
	getApplicationLists,
	changeApplicationStatus,
} from "@/api/decoration/application";
import { usePaging } from "@/hooks/usePaging";
import { getRoutePath } from "@/router";
import feedback from "@/utils/feedback";

const router = useRouter();

const queryParams = reactive({
	name: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
	fetchFun: getApplicationLists,
	params: queryParams,
});

const changeStatus = async (id: number) => {
	try {
		await changeApplicationStatus({ id });
	} finally {
		getLists();
	}
};

getLists();
</script>
