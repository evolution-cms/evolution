/**
 * Global Tailwind tokens for Evolution CMS manager and all modules
 */
module.exports = {
    content: [
        './manager/**/*.{php,html,tpl}',
        './packages/**/resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#2563eb',
                danger: '#dc2626',
                success: '#059669',
            },
            borderRadius: {xl: '1rem'},
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
};
