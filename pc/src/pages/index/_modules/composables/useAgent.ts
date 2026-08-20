import { getAllAgentList as getAgentListApi } from "@/api/agent";

export function useAgent() {
    const agentList = ref<any[]>([]);

    const getAgentList = async () => {
        const { lists } = await getAgentListApi({ page_size: 1500, page_no: 1 });
        agentList.value = lists.map((item) => ({
            name: item.name,
            id: item.id,
            image: item.image,
            group_id: item.group_id ?? 0,
            type: item.type ?? 1,
            source: item.source,
            permissions: item.permissions,
            member_level_ids: item.member_level_ids,
        }));
    };

    return { agentList, getAgentList };
}
