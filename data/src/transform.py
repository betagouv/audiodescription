import os, sys, json
import pandas as pd
from datetime import datetime

# Define the Excel file path
excel_file = 'data/ListeTestAudiodescription 20240719.xlsx'

# Read specific sheets into DataFrames
films = pd.read_excel(excel_file, sheet_name='Films')
natio = pd.read_excel(excel_file, sheet_name='Natio')
real = pd.read_excel(excel_file, sheet_name='Réal')

# Convert specific columns to strings and format them to remove `.0`
def format_float_as_int(column):
    column = column.fillna('')
    return column.apply(lambda x: str(int(x)) if pd.notna(x) and isinstance(x, float) and x.is_integer() else str(x))

films['VISA'] = format_float_as_int(films['VISA'])
films['PRODUCTION'] = format_float_as_int(films['PRODUCTION'])
films['ANNEE SORTIE SALLES'] = format_float_as_int(films['ANNEE SORTIE SALLES'])

natio = natio[['N°CNC', 'PAYS']]
real = real[['N°CNC', 'Nom_Realisateur']]

# A movie can have several directors and countries.
# Group the "Natio" DataFrame by 'N°CNC' and aggregate 'PAYS' into a list
natio_grouped = natio.groupby('N°CNC')['PAYS'].agg(list).reset_index()

# Group the "Réal" DataFrame by 'N°CNC' and aggregate 'Nom_Realisateur' into a list
real_grouped = real.groupby('N°CNC')['Nom_Realisateur'].agg(list).reset_index()

# Convert the PAYS column to a JSON string
natio_grouped['PAYS'] = natio_grouped['PAYS'].apply(json.dumps)

# Convert the Nom_Realisateur column to a JSON string
real_grouped['Nom_Realisateur'] = real_grouped['Nom_Realisateur'].apply(json.dumps)

# Merge the DataFrames on the "N°CNC" column
# This assumes you want an left join, which merges on rows where "N°CNC" is present in all DataFrames
result = films.merge(natio_grouped, on='N°CNC', how='left').merge(real_grouped, on='N°CNC', how='left')

# Get the current date and time
current_datetime = datetime.now()

# Format the datetime as 'DD-MM-YY-HH-MM-SS'
formatted_datetime = current_datetime.strftime('%d-%m-%y-%H-%M-%S')

result_folder = 'results'
os.makedirs(result_folder, exist_ok=True)

# Save the merged DataFrame to a new CSV file
result.to_csv(f'{result_folder}/{formatted_datetime}-result.csv', index=False)