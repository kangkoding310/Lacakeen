import { route as routeFunction } from 'ziggy-js';

declare global {
    // eslint-disable-next-line no-var
    var route: typeof routeFunction;
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof routeFunction;
    }
}
