import { definePreset } from '@primeuix/themes';
import Aura from '@primeuix/themes/aura';

const zinc = {
    0: '#ffffff',
    50: '{zinc.50}',
    100: '{zinc.100}',
    200: '{zinc.200}',
    300: '{zinc.300}',
    400: '{zinc.400}',
    500: '{zinc.500}',
    600: '{zinc.600}',
    700: '{zinc.700}',
    800: '{zinc.800}',
    900: '{zinc.900}',
    950: '{zinc.950}',
};

/**
 * Monochrome preset built on Aura. Primary actions are near-black in light
 * mode and near-white in dark mode; neutrals use the zinc scale so PrimeVue
 * components match the Tailwind/shadcn shell tokens in app.css.
 */
export const DbblPreset = definePreset(Aura, {
    semantic: {
        primary: {
            50: '{zinc.50}',
            100: '{zinc.100}',
            200: '{zinc.200}',
            300: '{zinc.300}',
            400: '{zinc.400}',
            500: '{zinc.500}',
            600: '{zinc.600}',
            700: '{zinc.700}',
            800: '{zinc.800}',
            900: '{zinc.900}',
            950: '{zinc.950}',
        },
        colorScheme: {
            light: {
                surface: zinc,
                primary: {
                    color: '{zinc.900}',
                    contrastColor: '#ffffff',
                    hoverColor: '{zinc.800}',
                    activeColor: '{zinc.700}',
                },
                highlight: {
                    background: '{zinc.900}',
                    focusBackground: '{zinc.800}',
                    color: '#ffffff',
                    focusColor: '#ffffff',
                },
            },
            dark: {
                surface: zinc,
                primary: {
                    color: '{zinc.50}',
                    contrastColor: '{zinc.950}',
                    hoverColor: '{zinc.200}',
                    activeColor: '{zinc.300}',
                },
                highlight: {
                    background: 'rgba(250, 250, 250, 0.14)',
                    focusBackground: 'rgba(250, 250, 250, 0.22)',
                    color: 'rgba(255, 255, 255, 0.92)',
                    focusColor: 'rgba(255, 255, 255, 0.92)',
                },
                content: {
                    background: '{surface.950}',
                    hoverBackground: '{surface.900}',
                    borderColor: '{surface.800}',
                },
                formField: {
                    background: '{surface.950}',
                    borderColor: '{surface.800}',
                    hoverBorderColor: '{surface.700}',
                },
                overlay: {
                    select: {
                        background: '{surface.900}',
                        borderColor: '{surface.800}',
                    },
                    popover: {
                        background: '{surface.900}',
                        borderColor: '{surface.800}',
                    },
                    modal: {
                        background: '{surface.900}',
                        borderColor: '{surface.800}',
                    },
                },
            },
        },
    },
});
