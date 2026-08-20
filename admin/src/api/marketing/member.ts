import request from "@/utils/request";

// ─── 会员等级 ──────────────────────────────────────────────
export const getMemberLevelList = (params?: any) =>
    request.get({ url: "/member.memberLevel/lists", params });

export const getMemberLevelDetail = (params: any) =>
    request.get({ url: "/member.memberLevel/detail", params });

export const addMemberLevel = (params: any) =>
    request.post({ url: "/member.memberLevel/add", params });

export const editMemberLevel = (params: any) =>
    request.post({ url: "/member.memberLevel/edit", params });

export const deleteMemberLevel = (params: any) =>
    request.post({ url: "/member.memberLevel/delete", params });

// ─── 会员用户 ──────────────────────────────────────────────
export const getMemberUserList = (params?: any) =>
    request.get({ url: "/member.memberUser/lists", params });

export const grantMember = (params: any) =>
    request.post({ url: "/member.memberUser/grant", params });

export const cancelMember = (params: any) =>
    request.post({ url: "/member.memberUser/cancel", params });
