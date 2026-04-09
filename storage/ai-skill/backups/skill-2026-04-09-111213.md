# AI Labeling Skill
**Versie:** v1.1
**Aangemaakt:** 2026-04-02
**Laatst bijgewerkt:** 2026-04-02
**Gebaseerd op:** 1 correctie

---

## Onze definities

### Labels
- **bug** — reproduceerbaar defect, iets werkt anders dan verwacht of beloofd
- **feature request** — klant vraagt om nieuwe of uitgebreide functionaliteit
- **onderzoek** — probleem is onduidelijk, meer informatie nodig voor classificatie
- **eigenlijk niet voor ons** — probleem ligt buiten onze verantwoordelijkheid of scope

### Impact
- **low** — klant kan verder werken, kleine hinder, geen tijdsdruk
- **medium** — klant heeft hinder maar heeft een workaround beschikbaar
- **high** — klant ligt stil, productie geblokkeerd, geen workaround mogelijk

---

## Geleerde regels uit correcties

### Regel 1: Feature requests met specifieke UI-implementatie vereisen onderzoek
Wanneer een klant een feature request doet en daarbij **specifieke UI-elementen of plaatsing** noemt (bijv. "icoontje rechtsboven", "toggle button in menu"), label dan **zowel [feature request] als [onderzoek]**. De functionele wens is duidelijk (feature request), maar de exacte implementatie en haalbaarheid van de voorgestelde UI vereist technisch onderzoek.

---

## Correctie voorbeelden

### Voorbeeld 1: Feature met UI-specificatie
**Ticket:** "Donkere modus toevoegen aan het dashboard"  
**Beschrijving:** "Ik wil een donkere modus optie hebben in mijn project. Ik wil daarvoor een icoontje rechtsboven, een soort toggle."  
**AI labels:** [feature request]  
**Agent labels:** [feature request, onderzoek]  
**Impact:** low (correct)  
**Waarom gecorrigeerd:** Klant specificeert niet alleen de functionaliteit (donkere modus) maar ook de gewenste implementatie (icoontje rechtsboven, toggle). Dit vereist onderzoek naar technische haalbaarheid en of deze UI-keuze past binnen het bestaande design.

---

## Moeilijke gevallen

*Wordt aangevuld naarmate het systeem leert.*