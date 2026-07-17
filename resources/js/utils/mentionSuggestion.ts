import MentionList from '@/Components/ui/MentionList.vue';
import type { TaskAssignee } from '@/types/task';
import type { MentionNodeAttrs } from '@tiptap/extension-mention';
import type { SuggestionOptions } from '@tiptap/suggestion';
import { VueRenderer } from '@tiptap/vue-3';

export function createMentionSuggestion(
    getUsers: () => TaskAssignee[]
): Omit<SuggestionOptions<TaskAssignee, MentionNodeAttrs>, 'editor'> {
    return {
        items: ({ query }) => {
            const needle = query.toLowerCase();
            return getUsers()
                .filter((user) => user.name.toLowerCase().includes(needle))
                .slice(0, 8);
        },
        render: () => {
            let component: VueRenderer;
            let unmount: (() => void) | undefined;

            return {
                onStart: (props) => {
                    component = new VueRenderer(MentionList, { props, editor: props.editor });
                    if (component.element) unmount = props.mount(component.element as HTMLElement);
                },
                onUpdate: (props) => {
                    component.updateProps(props);
                },
                onKeyDown: (props) => {
                    if (props.event.key === 'Escape') {
                        unmount?.();
                        return true;
                    }
                    return component.ref?.onKeyDown(props) ?? false;
                },
                onExit: () => {
                    unmount?.();
                    component.destroy();
                },
            };
        },
    };
}
