<template>
	<div>
		<el-card class="!border-none mt-4" shadow="never">
			<el-table :data="pager.lists" style="width: 100%">
				<el-table-column prop="id" label="ID" />
				<el-table-column label="头像" min-width="100">
					<template #default="{ row }">
						<el-avatar :src="row.avatar" :size="50" />
					</template>
				</el-table-column>
				<el-table-column
					label="昵称"
					prop="nickname"
					min-width="100"
					show-overflow-tooltip />
				<el-table-column prop="company_size" label="公司规模">
					<template #default="{ row }">
						{{ row.company_size }}人
					</template>
				</el-table-column>
				<el-table-column
					prop="company_name"
					label="公司名称"
					min-width="160" />
				<el-table-column
					prop="create_time"
					label="创建时间"
					min-width="140" />
				<el-table-column label="操作" width="140" fixed="right">
					<template #default="{ row }">
						<el-button
							v-perms="['user.survey/delete']"
							type="danger"
							link
							@click="handleDel(row.id)"
							>删除</el-button
						>
					</template>
				</el-table-column>
			</el-table>
			<div class="flex justify-end mt-4">
				<pagination v-model="pager" @change="getLists" />
			</div>
		</el-card>
	</div>
</template>

<script setup lang="ts">
import { userSurveyLists, userSurveyDel } from "@/api/consumer";
import { usePaging } from "@/hooks/usePaging";
import feedback from "@/utils/feedback";
import { ElMessageBox } from "element-plus";

const queryParams = reactive({
	user: "",
});
const { pager, getLists, resetPage, resetParams } = usePaging({
	fetchFun: userSurveyLists,
	params: queryParams,
});

const handleView = (row: any) => {
	ElMessageBox.alert(
		"<strong>proxy is <i>HTML</i> string</strong>",
		"HTML String",
		{
			dangerouslyUseHTMLString: true,
		}
	);
};

const handleDel = async (id: string) => {
	await feedback.confirm("确定要删除吗？");
	await userSurveyDel({ id });
	getLists();
};

getLists();
</script>

<style scoped></style>
