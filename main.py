from fastapi import FastAPI, File, UploadFile
from fastapi.responses import JSONResponse
import numpy as np
from PIL import Image
import io
import os

# Matikan log TensorFlow yang tidak perlu
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3' 

print("[1/3] Meng-import TensorFlow (tunggu sebentar)...")
try:
    import tensorflow as tf
except Exception as e:
    print(f"Gagal memuat TensorFlow: {e}")

app = FastAPI(title="EcoSort AI API", version="1.0")

# Placeholder untuk model
model = None

def load_model():
    global model
    if model is None:
        print("[2/3] Membaca model AI ke RAM...")
        MODEL_PATH = "ecosort_model_best.keras"
        model = tf.keras.models.load_model(MODEL_PATH)
        print("[3/3] Model berhasil dimuat!")
    return model

# Daftar 12 kelas (pastikan urutannya sama persis seperti saat training di Colab)
CLASS_NAMES = ['battery', 'biological', 'brown-glass', 'cardboard', 
               'clothes', 'green-glass', 'metal', 'paper', 
               'plastic', 'shoes', 'trash', 'white-glass']

def preprocess_image(image_bytes):
    # Mengubah gambar menjadi format array untuk TensorFlow
    img = Image.open(io.BytesIO(image_bytes)).convert("RGB")
    img = img.resize((224, 224))
    img_array = tf.keras.utils.img_to_array(img)
    img_array = tf.expand_dims(img_array, 0) # Menambah dimensi batch
    return img_array

@app.post("/api/predict")
async def predict_trash(file: UploadFile = File(...)):
    try:
        contents = await file.read()
        processed_image = preprocess_image(contents)
        
        # Load model secara lazy (hanya saat dibutuhkan)
        current_model = load_model()
        
        # Proses tebakan AI
        predictions = current_model.predict(processed_image)
        predicted_index = np.argmax(predictions[0])
        confidence = float(np.max(predictions[0]))
        
        return JSONResponse(content={
            "success": True,
            "category": CLASS_NAMES[predicted_index],
            "confidence": round(confidence * 100, 2), # Dibulatkan 2 angka di belakang koma
            "filename": file.filename
        })
    except Exception as e:
        return JSONResponse(content={
            "success": False,
            "error": str(e)
        }, status_code=500)

@app.get("/")
def read_root():
    return {"message": "Service AI EcoSort Berjalan Lancar!"}