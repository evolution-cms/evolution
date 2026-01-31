(() => {
  const THEME_KEY = "evo.theme"
  const THEME_LIGHT_KEY = "evo.theme.light"
  const THEME_DARK_KEY = "evo.theme.dark"
  const MODE_KEY = "evo.mode"
  const root = document.documentElement

  const getMenu = () => document.getElementById("mainMenu")
  const parseJson = (value, fallback) => {
    try {
      return JSON.parse(value)
    } catch (error) {
      return fallback
    }
  }
  const readStorage = (key, fallback) => {
    try {
      const value = localStorage.getItem(key)
      return value === null ? fallback : value
    } catch (error) {
      return fallback
    }
  }
  const writeStorage = (key, value) => {
    try {
      localStorage.setItem(key, value)
    } catch (error) {
    }
  }

  let menu = null
  let themes = []
  let lightThemes = []
  let darkThemes = []
  let defaultTheme = "light"
  let defaultMode = "light"
  let defaultLightTheme = "light"
  let defaultDarkTheme = "dark"
  let lastLightTheme = readStorage(THEME_LIGHT_KEY, defaultLightTheme)
  let lastDarkTheme = readStorage(THEME_DARK_KEY, defaultDarkTheme)

  let currentTheme = readStorage(THEME_KEY, defaultTheme)
  let currentMode = readStorage(MODE_KEY, defaultMode)

  const isDarkTheme = (theme, mode) => {
    if (mode === "dark") {
      return true
    }
    return theme === "dark" || darkThemes.includes(theme)
  }

  const themeGroup = (theme) => {
    if (darkThemes.includes(theme) || theme === "dark") {
      return "dark"
    }
    return "light"
  }

  const normalizeMode = (mode, theme) => {
    if (mode === "dark" || mode === "light") {
      return mode
    }
    return themeGroup(theme)
  }

  const syncLegacyMode = () => {
    const body = document.body
    if (!body) {
      return
    }
    const dark = isDarkTheme(currentTheme, currentMode)
    body.classList.toggle("dark", dark)
    body.classList.toggle("darkness", dark)
  }

  const applyTheme = (theme, mode) => {
    if (theme) {
      currentTheme = theme
      writeStorage(THEME_KEY, theme)
    }
    const nextMode = normalizeMode(mode, currentTheme)
    if (nextMode) {
      currentMode = nextMode
      writeStorage(MODE_KEY, currentMode)
    }
    const group = themeGroup(currentTheme)
    if (group === "dark") {
      lastDarkTheme = currentTheme
      writeStorage(THEME_DARK_KEY, currentTheme)
    } else {
      lastLightTheme = currentTheme
      writeStorage(THEME_LIGHT_KEY, currentTheme)
    }

    root.setAttribute("data-theme", currentTheme)
    root.setAttribute("data-theme-mode", currentMode)
    syncLegacyMode()
    updateToggle()
    updateDropdown()
    updateThemeGroups()
  }

  const updateToggle = () => {
    const toggle = document.querySelector("[data-evo-theme-toggle]")
    if (!toggle) {
      return
    }

    const moon = toggle.querySelector('[data-evo-icon="moon"]')
    const sun = toggle.querySelector('[data-evo-icon="sun"]')
    const dark = isDarkTheme(currentTheme, currentMode)

    if (moon) {
      moon.style.display = dark ? "inline-flex" : "none"
    }
    if (sun) {
      sun.style.display = dark ? "none" : "inline-flex"
    }
    toggle.setAttribute("aria-pressed", dark ? "true" : "false")
  }

  const updateDropdown = () => {
    document.querySelectorAll("[data-evo-theme]").forEach((item) => {
      const theme = item.getAttribute("data-evo-theme")
      if (theme === currentTheme) {
        item.classList.add("active")
      } else {
        item.classList.remove("active")
      }
    })
  }

  const updateThemeGroups = () => {
    const group = currentMode === "dark" ? "dark" : "light"
    document.querySelectorAll("[data-theme-group]").forEach((item) => {
      const itemGroup = item.getAttribute("data-theme-group")
      item.style.display = itemGroup === group ? "" : "none"
    })
  }

  const setTheme = (theme) => {
    if (!theme) {
      return
    }
    if (themes.length && !themes.includes(theme)) {
      return
    }
    const mode = themeGroup(theme)
    applyTheme(theme, mode)
  }

  const toggleMode = () => {
    const nextMode = currentMode === "dark" ? "light" : "dark"
    const nextTheme = nextMode === "dark" ? lastDarkTheme : lastLightTheme
    applyTheme(nextTheme, nextMode)
  }

  const hydrateFromMenu = () => {
    menu = getMenu()
    if (!menu) {
      return
    }

    themes = menu.dataset.evoThemes ? parseJson(menu.dataset.evoThemes, []) : []
    lightThemes = menu.dataset.evoThemesLight ? parseJson(menu.dataset.evoThemesLight, []) : []
    darkThemes = menu.dataset.evoThemesDark ? parseJson(menu.dataset.evoThemesDark, []) : []
    defaultTheme = menu.dataset.evoThemeDefault || "light"
    defaultMode = menu.dataset.evoModeDefault || "light"
    defaultLightTheme = lightThemes.includes(defaultTheme)
      ? defaultTheme
      : (lightThemes.includes("light") ? "light" : (lightThemes[0] || "light"))
    defaultDarkTheme = darkThemes.includes("dark")
      ? "dark"
      : (darkThemes[0] || defaultLightTheme)

    if (!lightThemes.includes(lastLightTheme)) {
      lastLightTheme = defaultLightTheme
      writeStorage(THEME_LIGHT_KEY, lastLightTheme)
    }
    if (!darkThemes.includes(lastDarkTheme)) {
      lastDarkTheme = defaultDarkTheme
      writeStorage(THEME_DARK_KEY, lastDarkTheme)
    }

    if (!currentTheme || (themes.length && !themes.includes(currentTheme))) {
      currentTheme = readStorage(THEME_KEY, defaultTheme)
    }
    currentMode = normalizeMode(readStorage(MODE_KEY, currentMode || defaultMode), currentTheme)
  }

  const bindDropdowns = () => {
    if (!menu) {
      return
    }

    const isNoopHref = (href) => {
      if (!href) {
        return true
      }
      const normalized = href.trim().toLowerCase()
      return normalized === "#" || normalized === "javascript:;" || normalized.startsWith("javascript:")
    }

    const runInlineHandler = (code, context, event) => {
      if (!code) {
        return true
      }
      try {
        const fn = new Function("event", code)
        const result = fn.call(context, event)
        if (result === false) {
          return false
        }
      } catch (error) {
        return true
      }
      return true
    }

    const openTarget = (href, target, title) => {
      if (!href) {
        return
      }
      const safeTitle = title || "blank"
      if (!target || target === "main" || target === "mainframe") {
        if (window.modx && modx.config && modx.config.global_tabs && typeof modx.tabs === "function") {
          modx.tabs({ url: href, title: safeTitle })
          return
        }
        if (window.main && window.main !== window) {
          window.main.location.href = href
          return
        }
        window.location.href = href
        return
      }
      if (target === "_blank") {
        window.open(href, "_blank")
        return
      }
      const frame = window.frames[target]
      if (frame) {
        frame.location.href = href
        return
      }
      window.open(href, target)
    }

    const closeOthers = (current) => {
      menu.querySelectorAll("details[open]").forEach((openDetail) => {
        if (!current) {
          openDetail.removeAttribute("open")
          return
        }
        if (openDetail === current) {
          return
        }
        if (openDetail.contains(current)) {
          return
        }
        openDetail.removeAttribute("open")
      })
    }

    menu.addEventListener("toggle", (event) => {
      const details = event.target
      if (!(details instanceof HTMLDetailsElement) || !details.open) {
        return
      }
      closeOthers(details)
    }, true)

    menu.addEventListener("click", (event) => {
      const summary = event.target.closest("summary")
      if (summary && summary.dataset && summary.dataset.menuLevel && summary.dataset.menuLevel !== "0") {
        const isCaret = event.target.closest("[data-menu-caret]")
        const isLink = event.target.closest("[data-menu-link]")
        if (!isCaret && !isLink) {
          event.preventDefault()
          event.stopPropagation()
          return
        }
      }

      const caret = event.target.closest("[data-menu-caret]")
      if (caret) {
        const details = caret.closest("details")
        if (details) {
          event.preventDefault()
          event.stopPropagation()
          const willOpen = !details.open
          details.open = willOpen
          if (willOpen) {
            closeOthers(details)
          }
        }
        return
      }

      const link = event.target.closest("[data-menu-link]")
      if (link) {
        const summary = link.closest("summary")
        if (!summary) {
          return
        }

        const href = summary.dataset.menuHref || ""
        const onclick = summary.dataset.menuOnclick || ""
        const target = summary.dataset.menuTarget || ""
        const title = summary.dataset.menuTitle || ""

        if (isNoopHref(href) && !onclick) {
          return
        }

        event.preventDefault()
        event.stopPropagation()

        if (!runInlineHandler(onclick, summary, event)) {
          return
        }

        if (!isNoopHref(href)) {
          openTarget(href, target, title)
        }

        const details = summary.closest("details")
        if (details) {
          details.open = false
        }
        closeOthers(null)
        return
      }

      const action = event.target.closest("details[open] a, details[open] button")
      if (!action || action.closest("summary")) {
        return
      }
      const details = action.closest("details")
      if (details) {
        details.removeAttribute("open")
      }
    })

    document.addEventListener("click", (event) => {
      if (!menu.contains(event.target)) {
        closeOthers(null)
      }
    })
  }

  const bindUi = () => {
    hydrateFromMenu()
    document.addEventListener("click", (event) => {
      const toggle = event.target.closest("[data-evo-theme-toggle]")
      if (toggle) {
        event.preventDefault()
        toggleMode()
        return
      }

      const themeItem = event.target.closest("[data-evo-theme]")
      if (themeItem) {
        event.preventDefault()
        setTheme(themeItem.getAttribute("data-evo-theme"))
      }
    })
    applyTheme(currentTheme, currentMode)
    bindDropdowns()
  }

  root.setAttribute("data-theme", currentTheme)
  root.setAttribute("data-theme-mode", currentMode)

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", bindUi)
  } else {
    bindUi()
  }
})()
