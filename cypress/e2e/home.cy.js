describe("Homepage", () => {
   it("affiche le titre de la page", () => {
       cy.visit("https://beta.gouv.fr");
       cy.findByRole('heading', {level:1})
           .should("be.visible")
           .should("have.text", 'Construisons ensemble les services publics numériques de demain')
   });

   it("recherche", () => {
       cy.visit("https://beta.gouv.fr");
       cy.findByRole("searchbox").type("fon");
   })
});