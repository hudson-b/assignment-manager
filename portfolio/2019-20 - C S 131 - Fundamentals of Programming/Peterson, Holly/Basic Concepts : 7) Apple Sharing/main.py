# Peterson, Holly 
# 2019-09-23 

numberOfGoats = input("How many goats?")
numberOfGoats = int(numberOfGoats)

numberOfApples = input("How many apples?")
numberOfApples = int(numberOfApples)

print("Each goat will receive",numberOfApples // numberOfGoats,"apples.")

print(numberOfApples % numberOfGoats,"apples will be left in the basket.")


