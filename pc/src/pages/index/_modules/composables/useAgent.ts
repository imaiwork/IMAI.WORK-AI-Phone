import { getAllAgentList as getAgentListApi } from "@/api/agent";

export function useAgent() {
    const agentList = ref<{ name: string; id: string; image: string }[]>([]);

    const getAgentList = async () => {
        const { lists } = await getAgentListApi({ page_size: 1500, page_no: 1 });
        agentList.value = lists.map((item) => ({ name: item.name, id: item.id, image: item.image }));
    };

    return { agentList, getAgentList };
}
